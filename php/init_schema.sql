-- PermitSales (PHP edition) PostgreSQL initialization schema.
--
-- Safe to import on either a fresh database or on top of an existing
-- install. Tables, indexes, triggers, and seed data are all guarded so
-- re-importing this file is idempotent and will upgrade an older
-- (pre-clients) schema in place.
--
--   psql "$DATABASE_URL" -f init_schema.sql

CREATE EXTENSION IF NOT EXISTS pgcrypto;

-- ---------------------------------------------------------------------
-- Tables
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS roles (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name TEXT NOT NULL UNIQUE CHECK (name IN ('admin', 'user')),
    description TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    role_id UUID NOT NULL REFERENCES roles(id) ON DELETE RESTRICT,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    full_name TEXT NOT NULL,
    phone TEXT,
    mailing_first_name TEXT,
    mailing_last_name TEXT,
    mailing_line1 TEXT,
    mailing_line2 TEXT,
    mailing_city TEXT,
    mailing_state TEXT,
    mailing_zip TEXT,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    last_login_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    deleted_at TIMESTAMPTZ,
    CONSTRAINT users_email_format CHECK (email ~* '^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$')
);

CREATE TABLE IF NOT EXISTS vehicles (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    make TEXT NOT NULL,
    model TEXT NOT NULL,
    color TEXT NOT NULL,
    license_plate TEXT NOT NULL,
    license_plate_region TEXT,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    deleted_at TIMESTAMPTZ,
    CONSTRAINT vehicles_license_plate_not_blank CHECK (length(trim(license_plate)) > 0)
);

CREATE TABLE IF NOT EXISTS credit_cards (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    cardholder_name TEXT NOT NULL,
    brand TEXT,
    encrypted_card_number BYTEA NOT NULL,
    card_number_iv BYTEA NOT NULL,
    card_number_auth_tag BYTEA NOT NULL,
    encrypted_exp_month BYTEA NOT NULL,
    exp_month_iv BYTEA NOT NULL,
    exp_month_auth_tag BYTEA NOT NULL,
    encrypted_exp_year BYTEA NOT NULL,
    exp_year_iv BYTEA NOT NULL,
    exp_year_auth_tag BYTEA NOT NULL,
    encrypted_cvc BYTEA,
    cvc_iv BYTEA,
    cvc_auth_tag BYTEA,
    last_four_hash TEXT NOT NULL,
    display_last_four TEXT NOT NULL,
    billing_zip TEXT,
    is_default BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    deleted_at TIMESTAMPTZ,
    CONSTRAINT credit_cards_display_last_four CHECK (display_last_four ~ '^[0-9]{4}$')
);

CREATE TABLE IF NOT EXISTS clients (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    slug TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    phone TEXT,
    public_phone TEXT,
    contact_phone TEXT,
    contact_name TEXT,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT clients_slug_format CHECK (slug ~ '^[a-z0-9]+(?:-[a-z0-9]+)*$')
);

CREATE TABLE IF NOT EXISTS parking_lots (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    client_id UUID NOT NULL REFERENCES clients(id) ON DELETE CASCADE,
    code TEXT NOT NULL,
    name TEXT NOT NULL,
    address TEXT,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (client_id, code)
);

-- Permit types are scoped per-client. Each client owns their own
-- catalog (with their own names, codes, and prices) so two clients can
-- both have, e.g., a "Monthly Permit" priced however they like.
CREATE TABLE IF NOT EXISTS permit_types (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    client_id UUID REFERENCES clients(id) ON DELETE CASCADE,
    code TEXT NOT NULL,
    name TEXT NOT NULL,
    description TEXT NOT NULL,
    cents_price INTEGER NOT NULL CHECK (cents_price >= 0),
    duration_days INTEGER NOT NULL CHECK (duration_days > 0),
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS permit_orders (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    client_id UUID REFERENCES clients(id) ON DELETE RESTRICT,
    lot_id UUID REFERENCES parking_lots(id) ON DELETE SET NULL,
    vehicle_id UUID REFERENCES vehicles(id) ON DELETE SET NULL,
    permit_type_id UUID NOT NULL REFERENCES permit_types(id) ON DELETE RESTRICT,
    credit_card_id UUID REFERENCES credit_cards(id) ON DELETE SET NULL,
    status TEXT NOT NULL DEFAULT 'pending'
        CHECK (status IN ('pending', 'paid', 'mailed', 'cancelled', 'refunded')),
    permit_number TEXT NOT NULL UNIQUE,
    cents_total INTEGER NOT NULL CHECK (cents_total >= 0),
    starts_on DATE NOT NULL,
    ends_on DATE NOT NULL,
    mailing_address TEXT,
    notes TEXT,
    approved_at TIMESTAMPTZ,
    approved_by UUID REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT permit_orders_dates_valid CHECK (ends_on >= starts_on)
);

CREATE TABLE IF NOT EXISTS auth_sessions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token_hash TEXT NOT NULL UNIQUE,
    user_agent TEXT,
    ip_address INET,
    expires_at TIMESTAMPTZ NOT NULL,
    revoked_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS audit_logs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    actor_user_id UUID REFERENCES users(id) ON DELETE SET NULL,
    action TEXT NOT NULL,
    entity_type TEXT NOT NULL,
    entity_id UUID,
    metadata JSONB NOT NULL DEFAULT '{}'::jsonb,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ---------------------------------------------------------------------
-- Schema upgrades for legacy (pre-clients) installs
--
-- These ALTERs are no-ops on a fresh install but bring an older schema
-- forward when this file is re-imported on top of a previous version.
-- ---------------------------------------------------------------------

ALTER TABLE clients ADD COLUMN IF NOT EXISTS phone TEXT;
ALTER TABLE clients ADD COLUMN IF NOT EXISTS public_phone TEXT;
ALTER TABLE clients ADD COLUMN IF NOT EXISTS contact_phone TEXT;
ALTER TABLE clients ADD COLUMN IF NOT EXISTS contact_name TEXT;

UPDATE clients
   SET public_phone = phone
 WHERE public_phone IS NULL
   AND phone IS NOT NULL
   AND phone <> '';

ALTER TABLE users ADD COLUMN IF NOT EXISTS mailing_first_name TEXT;
ALTER TABLE users ADD COLUMN IF NOT EXISTS mailing_last_name  TEXT;
ALTER TABLE users ADD COLUMN IF NOT EXISTS mailing_line1      TEXT;
ALTER TABLE users ADD COLUMN IF NOT EXISTS mailing_line2      TEXT;
ALTER TABLE users ADD COLUMN IF NOT EXISTS mailing_city       TEXT;
ALTER TABLE users ADD COLUMN IF NOT EXISTS mailing_state      TEXT;
ALTER TABLE users ADD COLUMN IF NOT EXISTS mailing_zip        TEXT;

ALTER TABLE permit_types ADD COLUMN IF NOT EXISTS client_id UUID
    REFERENCES clients(id) ON DELETE CASCADE;

ALTER TABLE permit_orders ADD COLUMN IF NOT EXISTS client_id UUID
    REFERENCES clients(id) ON DELETE RESTRICT;
ALTER TABLE permit_orders ADD COLUMN IF NOT EXISTS lot_id UUID
    REFERENCES parking_lots(id) ON DELETE SET NULL;
ALTER TABLE permit_orders ADD COLUMN IF NOT EXISTS approved_at TIMESTAMPTZ;
ALTER TABLE permit_orders ADD COLUMN IF NOT EXISTS approved_by UUID
    REFERENCES users(id) ON DELETE SET NULL;

-- ---------------------------------------------------------------------
-- Indexes
-- ---------------------------------------------------------------------

CREATE INDEX IF NOT EXISTS idx_clients_active           ON clients(is_active);
CREATE INDEX IF NOT EXISTS idx_parking_lots_client_id   ON parking_lots(client_id);
CREATE INDEX IF NOT EXISTS idx_permit_types_client_id   ON permit_types(client_id);
CREATE INDEX IF NOT EXISTS idx_permit_orders_client_id  ON permit_orders(client_id);
CREATE INDEX IF NOT EXISTS idx_permit_orders_lot_id     ON permit_orders(lot_id);
CREATE INDEX IF NOT EXISTS idx_users_role_id            ON users(role_id);
CREATE INDEX IF NOT EXISTS idx_users_active             ON users(is_active) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_vehicles_user_id         ON vehicles(user_id);
CREATE INDEX IF NOT EXISTS idx_vehicles_license_plate   ON vehicles(upper(license_plate)) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_credit_cards_user_id     ON credit_cards(user_id);
CREATE INDEX IF NOT EXISTS idx_credit_cards_default     ON credit_cards(user_id, is_default) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_permit_orders_user_id    ON permit_orders(user_id);
CREATE INDEX IF NOT EXISTS idx_permit_orders_status     ON permit_orders(status);
CREATE INDEX IF NOT EXISTS idx_auth_sessions_user_id    ON auth_sessions(user_id);
CREATE INDEX IF NOT EXISTS idx_auth_sessions_expires_at ON auth_sessions(expires_at);
CREATE INDEX IF NOT EXISTS idx_audit_logs_actor_user_id ON audit_logs(actor_user_id);
CREATE INDEX IF NOT EXISTS idx_audit_logs_entity        ON audit_logs(entity_type, entity_id);

-- ---------------------------------------------------------------------
-- updated_at trigger function + per-table triggers
-- ---------------------------------------------------------------------

CREATE OR REPLACE FUNCTION set_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_roles_updated_at         ON roles;
DROP TRIGGER IF EXISTS trg_users_updated_at         ON users;
DROP TRIGGER IF EXISTS trg_vehicles_updated_at      ON vehicles;
DROP TRIGGER IF EXISTS trg_credit_cards_updated_at  ON credit_cards;
DROP TRIGGER IF EXISTS trg_clients_updated_at       ON clients;
DROP TRIGGER IF EXISTS trg_parking_lots_updated_at  ON parking_lots;
DROP TRIGGER IF EXISTS trg_permit_types_updated_at  ON permit_types;
DROP TRIGGER IF EXISTS trg_permit_orders_updated_at ON permit_orders;

CREATE TRIGGER trg_roles_updated_at
BEFORE UPDATE ON roles
FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TRIGGER trg_users_updated_at
BEFORE UPDATE ON users
FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TRIGGER trg_vehicles_updated_at
BEFORE UPDATE ON vehicles
FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TRIGGER trg_credit_cards_updated_at
BEFORE UPDATE ON credit_cards
FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TRIGGER trg_clients_updated_at
BEFORE UPDATE ON clients
FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TRIGGER trg_parking_lots_updated_at
BEFORE UPDATE ON parking_lots
FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TRIGGER trg_permit_types_updated_at
BEFORE UPDATE ON permit_types
FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TRIGGER trg_permit_orders_updated_at
BEFORE UPDATE ON permit_orders
FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- ---------------------------------------------------------------------
-- Seed data
-- ---------------------------------------------------------------------

INSERT INTO roles (name, description)
VALUES
    ('admin', 'Platform administrator with access to metrics, users, vehicles, and orders.'),
    ('user', 'Monthly parking customer who manages vehicles, payment cards, and permit orders.')
ON CONFLICT (name) DO NOTHING;

-- Seed three demo clients. Each client owns their own parking lots and
-- their own permit catalog so prices/names/codes can drift over time.
INSERT INTO clients (slug, name)
VALUES
    ('rancho-cucamonga', 'Rancho Cucamonga'),
    ('covina',           'Covina'),
    ('daneville',        'Daneville')
ON CONFLICT (slug) DO NOTHING;

-- A starter parking lot for each seeded client. Admins can rename/add more.
INSERT INTO parking_lots (client_id, code, name, address)
SELECT c.id, 'MAIN', c.name || ' Main Lot', NULL
  FROM clients c
 WHERE c.slug IN ('rancho-cucamonga', 'covina', 'daneville')
ON CONFLICT (client_id, code) DO NOTHING;

-- ---------------------------------------------------------------------
-- Backfill + lock down per-client schema (legacy installs)
--
-- After clients are seeded we can:
--   1. Reassign any orphan permit_types / permit_orders to a default
--      client so the NOT NULL + UNIQUE constraints below can be added
--      safely on top of a pre-clients schema.
--   2. Replace the legacy global UNIQUE(code) on permit_types with a
--      per-client UNIQUE(client_id, code).
--   3. Promote client_id to NOT NULL on both permit_types and permit_orders.
--
-- All steps are idempotent: each is a no-op once already applied.
-- ---------------------------------------------------------------------

UPDATE permit_types
   SET client_id = (SELECT id FROM clients WHERE slug = 'rancho-cucamonga' LIMIT 1)
 WHERE client_id IS NULL;

UPDATE permit_orders po
   SET client_id = pt.client_id
  FROM permit_types pt
 WHERE pt.id = po.permit_type_id AND po.client_id IS NULL;

DO $$ BEGIN
    IF EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'permit_types_code_key') THEN
        ALTER TABLE permit_types DROP CONSTRAINT permit_types_code_key;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'permit_types_client_id_code_key') THEN
        ALTER TABLE permit_types
            ADD CONSTRAINT permit_types_client_id_code_key UNIQUE (client_id, code);
    END IF;
END $$;

DO $$ BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.columns
         WHERE table_name = 'permit_types' AND column_name = 'client_id'
           AND is_nullable = 'YES'
    ) AND NOT EXISTS (SELECT 1 FROM permit_types WHERE client_id IS NULL) THEN
        ALTER TABLE permit_types ALTER COLUMN client_id SET NOT NULL;
    END IF;
    IF EXISTS (
        SELECT 1 FROM information_schema.columns
         WHERE table_name = 'permit_orders' AND column_name = 'client_id'
           AND is_nullable = 'YES'
    ) AND NOT EXISTS (SELECT 1 FROM permit_orders WHERE client_id IS NULL) THEN
        ALTER TABLE permit_orders ALTER COLUMN client_id SET NOT NULL;
    END IF;
END $$;

-- Default permit catalog seeded per-client. Re-running is a no-op
-- thanks to the UNIQUE(client_id, code) constraint above.
INSERT INTO permit_types (client_id, code, name, description, cents_price, duration_days)
SELECT c.id, t.code, t.name, t.description, t.cents_price, t.duration_days
  FROM clients c
  CROSS JOIN (VALUES
        ('DAY',     'Day Pass',           'Single 24-hour parking permit, mailed-free digital pass.',      900,   1),
        ('WEEK',    'Weekly Permit',      '7-day parking permit for short-term stays and visitors.',     3500,   7),
        ('MONTH',   'Monthly Permit',     'Reserved monthly parking with renewable auto-billing.',      11500,  30),
        ('QUARTER', 'Quarterly Permit',   '90-day permit with priority enforcement support.',           31500,  90),
        ('ANNUAL',  'Annual Permit',      'Best-value yearly permit, priced for committed commuters.',  99000, 365)
   ) AS t(code, name, description, cents_price, duration_days)
 WHERE c.slug IN ('rancho-cucamonga', 'covina', 'daneville')
ON CONFLICT (client_id, code) DO NOTHING;
