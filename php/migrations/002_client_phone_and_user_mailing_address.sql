-- Migration 002: per-client phone numbers and persistent per-user
-- mailing addresses.
--
-- Apply with:
--   psql "$DATABASE_URL" -f migrations/002_client_phone_and_user_mailing_address.sql
--
-- Safe to re-run: every step uses ADD COLUMN IF NOT EXISTS.

BEGIN;

-- Each client now has its own phone number for customer-support call-outs.
ALTER TABLE clients ADD COLUMN IF NOT EXISTS phone TEXT;

-- Customers' mailing address is now stored on the user record once and
-- reused for every checkout, with an "Edit" affordance on the dashboard.
ALTER TABLE users ADD COLUMN IF NOT EXISTS mailing_first_name TEXT;
ALTER TABLE users ADD COLUMN IF NOT EXISTS mailing_last_name  TEXT;
ALTER TABLE users ADD COLUMN IF NOT EXISTS mailing_line1      TEXT;
ALTER TABLE users ADD COLUMN IF NOT EXISTS mailing_line2      TEXT;
ALTER TABLE users ADD COLUMN IF NOT EXISTS mailing_city       TEXT;
ALTER TABLE users ADD COLUMN IF NOT EXISTS mailing_state      TEXT;
ALTER TABLE users ADD COLUMN IF NOT EXISTS mailing_zip        TEXT;

COMMIT;
