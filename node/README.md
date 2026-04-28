# PermitSales

Design-forward parking permit sales platform scaffold for monthly parking customers and administrators.

## Stack

- Next.js, React, TypeScript, Tailwind CSS, Framer Motion
- Express, TypeScript, PostgreSQL
- JWT authentication with bcrypt password hashing
- AES-256-GCM application-level encryption for stored credit-card data

## Structure

```txt
.
├── init_schema.sql
├── apps/
│   ├── api/    # Express API
│   └── web/    # Next.js frontend
└── packages/
    └── shared/ # Shared zod schemas and types
```

## Database

Import the schema manually:

```bash
psql "$DATABASE_URL" -f init_schema.sql
```

The credit-card table stores ciphertext, IVs, and authentication tags for sensitive fields. It also stores `display_last_four` for UI presentation and a peppered SHA-256 hash of the last four digits for safe lookup/verification use cases.

## Environment

Create `apps/api/.env`:

```bash
DATABASE_URL=postgres://postgres:postgres@localhost:5432/permitsales
JWT_SECRET=replace-with-at-least-32-characters
CARD_ENCRYPTION_KEY=$(openssl rand -base64 32)
LAST_FOUR_HASH_PEPPER=replace-with-at-least-16-characters
CORS_ORIGIN=http://localhost:3000
PORT=4000
```

Create `apps/web/.env.local`:

```bash
NEXT_PUBLIC_API_URL=http://localhost:4000
NEXT_PUBLIC_DAY_PASS_URL=https://permitsales.com/day-pass
```

## Development

```bash
npm install
npm run dev
```

The landing page offers separate monthly account and single-day pass paths. Vehicle management intentionally collects make, model, color, and license plate only; VIN is not collected anywhere.
