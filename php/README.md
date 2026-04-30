# PermitSales (PHP edition)

A second implementation of the [permit-sales.com](https://www.permit-sales.com) parking-permit
sales website, built with **vanilla PHP 8**, **jQuery / HTML5** on the front end, and
**PostgreSQL** for storage.

It mirrors the spirit of the original site (Web Solutions, Fulfillment, Management, and
Enforcement) but with a modern, design-forward UI: a hero section, permit-tier cards,
how-it-works steps, customer testimonials, account self-service, and an admin console.

## Stack

- PHP 8.1+, no framework — small custom router / controller layer in `src/`
- PostgreSQL 13+ via PDO (`pgsql`)
- jQuery 3 + HTML5 + plain CSS (custom design tokens, no Tailwind)
- AES-256-GCM application-level encryption for stored card data
- Bcrypt password hashing, CSRF tokens, secure session cookies

## Layout

```txt
php/
├── README.md
├── .env.example
├── init_schema.sql
├── composer.json           # autoload only, no runtime deps required
├── public/                 # web root – point IIS / Apache / Nginx here
│   ├── index.php           # front controller
│   ├── web.config          # IIS 10 URL Rewrite + security rules
│   └── assets/             # css, js, svg images
├── src/                    # framework + controllers
└── views/                  # HTML5 + PHP templates
```

## Setup

1. Install PostgreSQL and create a database, e.g. `createdb permitsales_php`.
2. Import the schema:

   ```bash
   psql "$DATABASE_URL" -f init_schema.sql
   ```

3. Copy `.env.example` → `.env` and fill in the values.
4. (Optional) `composer dump-autoload` if you want PSR-4 autoloading via Composer
   instead of the included plain autoloader.
5. Run it. The app is designed to run on **IIS 10** out of the box, and also
   works with Apache, Nginx, or PHP's built-in server. See
   [Running on IIS 10](#running-on-iis-10) below, or for quick local
   development:

   ```bash
   php -S 127.0.0.1:8000 -t public
   ```

6. Visit <http://127.0.0.1:8000> (or whatever URL your IIS site is bound to).

## Running on IIS 10

The included `public/web.config` configures IIS 10 to serve this app with no
additional rewriting or scripting. You only need to point an IIS site (or
virtual directory) at the `public/` folder.

### Server prerequisites (one-time)

1. **Enable IIS with CGI**
   - Server Manager → *Add Roles and Features* → *Web Server (IIS)* →
     *Application Development* → enable **CGI**.
   - Or from PowerShell (as Administrator):

     ```powershell
     Enable-WindowsOptionalFeature -Online -FeatureName IIS-CGI -All
     ```

2. **Install PHP 8.1+ (Non-Thread-Safe x64 build)** from
   <https://windows.php.net/download/>. Unzip to e.g. `C:\PHP`. In
   `C:\PHP\php.ini` make sure the following are uncommented and the extensions
   match what the app uses:

   ```ini
   extension_dir = "ext"
   extension=pdo_pgsql
   extension=pgsql
   extension=openssl
   extension=mbstring
   extension=intl
   ```

3. **Register PHP with IIS** as a FastCGI handler. From an elevated
   PowerShell:

   ```powershell
   & "$env:windir\system32\inetsrv\appcmd.exe" set config /section:system.webServer/fastCGI /+"[fullPath='C:\PHP\php-cgi.exe']"
   & "$env:windir\system32\inetsrv\appcmd.exe" set config /section:system.webServer/handlers /+"[name='PHP_via_FastCGI',path='*.php',verb='*',modules='FastCgiModule',scriptProcessor='C:\PHP\php-cgi.exe',resourceType='Either']"
   ```

4. **Install the URL Rewrite Module 2.x** from
   <https://www.iis.net/downloads/microsoft/url-rewrite>. The bundled
   `web.config` uses standard `<rewrite>` rules that ship with this module.

### Site setup

1. In **IIS Manager**, *Add Website*:
   - **Site name:** `PermitSales`
   - **Physical path:** the absolute path to the `php/public/` folder
   - **Binding:** whatever hostname / port you want (e.g. `*:80`)
2. Make sure the application pool's identity (e.g. `IIS APPPOOL\PermitSales`)
   has **Read** access to the project files and **Read/Write** to any folder
   you intend to use for sessions or uploads.
3. Place your `.env` file one level **above** the web root (in `php/`,
   alongside `src/`), not inside `public/`. The bundled `web.config` also
   blocks `.env` defensively.
4. Browse to the site. URL routing, static assets, CSRF, and sessions all
   work without further configuration.

### What `public/web.config` does

- Routes every request that doesn't map to a real file or directory to
  `index.php` — the same front-controller behavior as the Apache `.htaccess`
  or `php -S … -t public` would give you.
- Hides `src/`, `views/`, `vendor/`, `.git/`, and any dotfile from the web.
- Blocks direct downloads of `.env`, `.sql`, and `.lock` files.
- Adds correct MIME types for `.svg`, `.woff`, `.woff2`, and
  `.webmanifest` assets.

### Configuring environment on IIS

You have three options. They are all checked, in this order, and the first
match wins (so a value set in IIS overrides a value in `.env`):

1. **Real environment variables / IIS FastCGI variables.** Recommended
   for production. Set them on the FastCGI handler so they are pushed
   into every PHP worker. There is a commented `<environmentVariables>`
   example at the bottom of `public/web.config`; uncomment it and fill in
   real values. You can also set them in *IIS Manager → FastCGI Settings →
   (your `php-cgi.exe`) → Edit → Environment Variables*.
2. **`.env` file.** The bootstrap looks for it in this order:
   1. The path in the `PERMITSALES_ENV_FILE` env var (if set),
   2. `php/.env` (alongside `src/`, `views/`, `composer.json`),
   3. `php/public/.env` (in case you put it next to `index.php`),
   4. one level above `php/`.

   The recommended location is `php/.env`. Keep it **above** the web root.

#### Common IIS gotchas with `.env`

- **UTF-8 BOM from Notepad.** Notepad and some PowerShell redirects save
  files with a UTF-8 BOM (`EF BB BF`) at the start, which would otherwise
  rename the first key to something like `\xEF\xBB\xBFDATABASE_URL` and
  make `getenv('DATABASE_URL')` return `false`. The loader strips the BOM
  automatically, but it is still safer to save `.env` as plain UTF-8 (no
  BOM) — VS Code, Notepad++, or `Set-Content -Encoding utf8NoBOM` all do
  this.
- **NTFS permissions.** The IIS application pool identity (default:
  `IIS APPPOOL\<sitename>`) must have **Read** on `php\.env`. Right-click
  the file → *Properties* → *Security* → *Edit* → *Add* →
  `IIS APPPOOL\PermitSales` → Read.
- **Where to put it.** `.env` should be in `php\` (one level above
  `public\`). The shipped `web.config` blocks `.env` from being served
  even if it ends up inside `public\`.
- **Diagnosing.** Set `APP_ENV=dev` (in the IIS env vars or in `.env`)
  and reload the page. The error page will print every path the loader
  tried and the result for each one (`missing`, `unreadable`,
  `loaded — N variable(s)`).

## Environment variables (`.env`)

| key                    | description                                                 |
| ---------------------- | ----------------------------------------------------------- |
| `DATABASE_URL`         | `postgres://user:pass@host:5432/permitsales_php` (URL-encode `#`, `@`, `/`, `?`, `:` etc. inside the username/password as `%23`, `%40`, `%2F`, `%3F`, `%3A`) |
| `APP_SECRET`           | ≥32-char string used to sign session / CSRF tokens          |
| `CARD_ENCRYPTION_KEY`  | base64 32-byte key for AES-256-GCM card encryption          |
| `LAST_FOUR_PEPPER`     | ≥16-char pepper for hashing card last-four digits           |
| `APP_ENV`              | `dev` or `prod`                                             |

Generate keys quickly:

```bash
php -r "echo bin2hex(random_bytes(32)).PHP_EOL;"             # APP_SECRET
php -r "echo base64_encode(random_bytes(32)).PHP_EOL;"       # CARD_ENCRYPTION_KEY
php -r "echo bin2hex(random_bytes(16)).PHP_EOL;"             # LAST_FOUR_PEPPER
```

## Notes

- Vehicle records intentionally collect **make, model, color, license plate** only — no
  VIN — matching the policy of the Node sibling project.
- Card numbers, expirations, and CVC are encrypted at rest with AES-256-GCM. Only a
  `display_last_four` and a peppered SHA-256 hash of the last four are usable in
  queries / UI.
- All form posts are CSRF-protected.
- Tested on PHP 8.1, 8.2, 8.3.
