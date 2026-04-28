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
├── public/                 # web root – point Apache/Nginx/`php -S` here
│   ├── index.php           # front controller
│   ├── .htaccess
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
5. Start the dev server:

   ```bash
   php -S 127.0.0.1:8000 -t public
   ```

6. Visit <http://127.0.0.1:8000>.

## Environment variables (`.env`)

| key                    | description                                                 |
| ---------------------- | ----------------------------------------------------------- |
| `DATABASE_URL`         | `postgres://user:pass@host:5432/permitsales_php`            |
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
