-- Migration v3: Add phone_imeis and invoice_line_imeis tables
-- Run this on production to add IMEI support

BEGIN;

CREATE TABLE IF NOT EXISTS phone_imeis (
    id SERIAL PRIMARY KEY,
    phone_id INTEGER NOT NULL REFERENCES phones(id) ON DELETE CASCADE,
    imei VARCHAR(100) UNIQUE NOT NULL,
    status VARCHAR(20) DEFAULT 'in_stock' CHECK (status IN ('in_stock', 'sold')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_phone_imeis_phone ON phone_imeis(phone_id);
CREATE INDEX IF NOT EXISTS idx_phone_imeis_status ON phone_imeis(phone_id, status);

CREATE TABLE IF NOT EXISTS invoice_line_imeis (
    id SERIAL PRIMARY KEY,
    invoice_line_id INTEGER NOT NULL REFERENCES invoice_lines(id) ON DELETE CASCADE,
    phone_imei_id INTEGER NOT NULL REFERENCES phone_imeis(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_invoice_line_imeis_line ON invoice_line_imeis(invoice_line_id);
CREATE INDEX IF NOT EXISTS idx_invoice_line_imeis_imei ON invoice_line_imeis(phone_imei_id);

COMMIT;

-- Optionally, manually migrate existing barcode/stored IMEIs if you had any serialized data.
-- =====================================================
-- Migration v3 : Gestion des IMEI individuels
-- =====================================================

-- Table des IMEI individuels par téléphone
CREATE TABLE IF NOT EXISTS phone_imeis (
    id SERIAL PRIMARY KEY,
    phone_id INTEGER NOT NULL REFERENCES phones(id) ON DELETE CASCADE,
    imei VARCHAR(100) UNIQUE NOT NULL,
    status VARCHAR(20) DEFAULT 'in_stock' CHECK (status IN ('in_stock', 'sold')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_phone_imeis_phone ON phone_imeis(phone_id);
CREATE INDEX IF NOT EXISTS idx_phone_imeis_status ON phone_imeis(phone_id, status);

-- Table de liaison IMEI / lignes de facture
CREATE TABLE IF NOT EXISTS invoice_line_imeis (
    id SERIAL PRIMARY KEY,
    invoice_line_id INTEGER NOT NULL REFERENCES invoice_lines(id) ON DELETE CASCADE,
    phone_imei_id INTEGER NOT NULL REFERENCES phone_imeis(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_invoice_line_imeis_line ON invoice_line_imeis(invoice_line_id);
CREATE INDEX IF NOT EXISTS idx_invoice_line_imeis_imei ON invoice_line_imeis(phone_imei_id);
