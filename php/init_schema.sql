-- PermitSales (PHP edition) PostgreSQL initialization schema.
-- Import manually with: psql "$DATABASE_URL" -f init_schema.sql

CREATE EXTENSION IF NOT EXISTS pgcrypto;

CREATE TABLE roles (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name TEXT NOT NULL UNIQUE CHECK (name IN ('admin', 'user')),
    description TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    role_id UUID NOT NULL REFERENCES roles(id) ON DELETE RESTRICT,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    full_name TEXT NOT NULL,
    phone TEXT,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    last_login_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    deleted_at TIMESTAMPTZ,
    CONSTRAINT users_email_format CHECK (email ~* '^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$')
);

CREATE TABLE vehicles (
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

CREATE TABLE credit_cards (
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

CREATE TABLE permit_types (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    code TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    description TEXT NOT NULL,
    cents_price INTEGER NOT NULL CHECK (cents_price >= 0),
    duration_days INTEGER NOT NULL CHECK (duration_days > 0),
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE permit_orders (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
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
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT permit_orders_dates_valid CHECK (ends_on >= starts_on)
);

CREATE TABLE auth_sessions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token_hash TEXT NOT NULL UNIQUE,
    user_agent TEXT,
    ip_address INET,
    expires_at TIMESTAMPTZ NOT NULL,
    revoked_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE audit_logs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    actor_user_id UUID REFERENCES users(id) ON DELETE SET NULL,
    action TEXT NOT NULL,
    entity_type TEXT NOT NULL,
    entity_id UUID,
    metadata JSONB NOT NULL DEFAULT '{}'::jsonb,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_users_role_id ON users(role_id);
CREATE INDEX idx_users_active ON users(is_active) WHERE deleted_at IS NULL;
CREATE INDEX idx_vehicles_user_id ON vehicles(user_id);
CREATE INDEX idx_vehicles_license_plate ON vehicles(upper(license_plate)) WHERE deleted_at IS NULL;
CREATE INDEX idx_credit_cards_user_id ON credit_cards(user_id);
CREATE INDEX idx_credit_cards_default ON credit_cards(user_id, is_default) WHERE deleted_at IS NULL;
CREATE INDEX idx_permit_orders_user_id ON permit_orders(user_id);
CREATE INDEX idx_permit_orders_status ON permit_orders(status);
CREATE INDEX idx_auth_sessions_user_id ON auth_sessions(user_id);
CREATE INDEX idx_auth_sessions_expires_at ON auth_sessions(expires_at);
CREATE INDEX idx_audit_logs_actor_user_id ON audit_logs(actor_user_id);
CREATE INDEX idx_audit_logs_entity ON audit_logs(entity_type, entity_id);

CREATE OR REPLACE FUNCTION set_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

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

CREATE TRIGGER trg_permit_types_updated_at
BEFORE UPDATE ON permit_types
FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TRIGGER trg_permit_orders_updated_at
BEFORE UPDATE ON permit_orders
FOR EACH ROW EXECUTE FUNCTION set_updated_at();

INSERT INTO roles (name, description)
VALUES
    ('admin', 'Platform administrator with access to metrics, users, vehicles, and orders.'),
    ('user', 'Monthly parking customer who manages vehicles, payment cards, and permit orders.')
ON CONFLICT (name) DO NOTHING;

INSERT INTO permit_types (code, name, description, cents_price, duration_days)
VALUES
    ('DAY',     'Day Pass',           'Single 24-hour parking permit, mailed-free digital pass.',     900,    1),
    ('WEEK',    'Weekly Permit',      '7-day parking permit for short-term stays and visitors.',    3500,    7),
    ('MONTH',   'Monthly Permit',     'Reserved monthly parking with renewable auto-billing.',     11500,   30),
    ('QUARTER', 'Quarterly Permit',   '90-day permit with priority enforcement support.',          31500,   90),
    ('ANNUAL',  'Annual Permit',      'Best-value yearly permit, priced for committed commuters.', 99000,  365)
ON CONFLICT (code) DO NOTHING;
