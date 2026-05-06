-- Migration 003: split the existing single "phone" field on clients
-- into a public-facing number (shown to customers on the dashboard)
-- plus an internal support contact (name + phone) only visible in
-- the admin console.
--
-- Apply with:
--   psql "$DATABASE_URL" -f migrations/003_clients_contact_info.sql
--
-- Safe to re-run: each step is guarded with ADD COLUMN IF NOT EXISTS
-- and the data backfill is idempotent.

BEGIN;

-- New per-client contact metadata.
--   public_phone  → the number we display to customers as the "call
--                   to expedite" line on the dashboard.
--   contact_phone → an internal support phone for the client's
--                   account manager (admins only).
--   contact_name  → the human at the client we email/call about
--                   their account (admins only).
ALTER TABLE clients ADD COLUMN IF NOT EXISTS public_phone  TEXT;
ALTER TABLE clients ADD COLUMN IF NOT EXISTS contact_phone TEXT;
ALTER TABLE clients ADD COLUMN IF NOT EXISTS contact_name  TEXT;

-- Backfill: any value already saved in the legacy `phone` column
-- (added by migration 002) was the phone we showed customers, so it
-- becomes the new `public_phone` for that client.
UPDATE clients
   SET public_phone = phone
 WHERE public_phone IS NULL
   AND phone IS NOT NULL
   AND phone <> '';

COMMIT;
