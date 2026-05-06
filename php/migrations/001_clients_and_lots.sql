-- Migration 001: introduce multi-tenant clients + parking lots and
-- scope the permit catalog and orders per client.
--
-- Apply with:
--   psql "$DATABASE_URL" -f migrations/001_clients_and_lots.sql
--
-- Safe to re-run; every step is guarded with IF NOT EXISTS / ON CONFLICT.

BEGIN;

CREATE TABLE IF NOT EXISTS clients (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    slug TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
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

CREATE INDEX IF NOT EXISTS idx_clients_active ON clients(is_active);
CREATE INDEX IF NOT EXISTS idx_parking_lots_client_id ON parking_lots(client_id);

DO $$ BEGIN
    PERFORM 1 FROM pg_trigger WHERE tgname = 'trg_clients_updated_at';
    IF NOT FOUND THEN
        CREATE TRIGGER trg_clients_updated_at
        BEFORE UPDATE ON clients
        FOR EACH ROW EXECUTE FUNCTION set_updated_at();
    END IF;
    PERFORM 1 FROM pg_trigger WHERE tgname = 'trg_parking_lots_updated_at';
    IF NOT FOUND THEN
        CREATE TRIGGER trg_parking_lots_updated_at
        BEFORE UPDATE ON parking_lots
        FOR EACH ROW EXECUTE FUNCTION set_updated_at();
    END IF;
END $$;

INSERT INTO clients (slug, name)
VALUES
    ('rancho-cucamonga', 'Rancho Cucamonga'),
    ('covina',           'Covina'),
    ('daneville',        'Daneville')
ON CONFLICT (slug) DO NOTHING;

INSERT INTO parking_lots (client_id, code, name, address)
SELECT c.id, 'MAIN', c.name || ' Main Lot', NULL
  FROM clients c
 WHERE c.slug IN ('rancho-cucamonga', 'covina', 'daneville')
ON CONFLICT (client_id, code) DO NOTHING;

-- permit_types: add client_id, replace global UNIQUE(code) with
-- UNIQUE(client_id, code). Existing rows are reassigned to the first
-- seeded client (Rancho Cucamonga) so they stay valid; admins can
-- re-home them later.
ALTER TABLE permit_types ADD COLUMN IF NOT EXISTS client_id UUID
    REFERENCES clients(id) ON DELETE CASCADE;

UPDATE permit_types
   SET client_id = (SELECT id FROM clients WHERE slug = 'rancho-cucamonga' LIMIT 1)
 WHERE client_id IS NULL;

ALTER TABLE permit_types ALTER COLUMN client_id SET NOT NULL;

DO $$ BEGIN
    IF EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'permit_types_code_key'
    ) THEN
        ALTER TABLE permit_types DROP CONSTRAINT permit_types_code_key;
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'permit_types_client_id_code_key'
    ) THEN
        ALTER TABLE permit_types
            ADD CONSTRAINT permit_types_client_id_code_key UNIQUE (client_id, code);
    END IF;
END $$;

CREATE INDEX IF NOT EXISTS idx_permit_types_client_id ON permit_types(client_id);

-- Seed each demo client with a default catalog if they don't have any
-- permit types yet (covers freshly seeded clients on existing installs).
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

-- permit_orders: tie every order to a client (and optionally a lot),
-- and track who approved a pending sale so the audit trail is intact.
ALTER TABLE permit_orders ADD COLUMN IF NOT EXISTS client_id UUID
    REFERENCES clients(id) ON DELETE RESTRICT;
ALTER TABLE permit_orders ADD COLUMN IF NOT EXISTS lot_id UUID
    REFERENCES parking_lots(id) ON DELETE SET NULL;
ALTER TABLE permit_orders ADD COLUMN IF NOT EXISTS approved_at TIMESTAMPTZ;
ALTER TABLE permit_orders ADD COLUMN IF NOT EXISTS approved_by UUID
    REFERENCES users(id) ON DELETE SET NULL;

UPDATE permit_orders po
   SET client_id = pt.client_id
  FROM permit_types pt
 WHERE pt.id = po.permit_type_id AND po.client_id IS NULL;

ALTER TABLE permit_orders ALTER COLUMN client_id SET NOT NULL;

CREATE INDEX IF NOT EXISTS idx_permit_orders_client_id ON permit_orders(client_id);
CREATE INDEX IF NOT EXISTS idx_permit_orders_lot_id ON permit_orders(lot_id);

COMMIT;
