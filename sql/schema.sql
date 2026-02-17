-- =====================================================
-- Script de création de la base de données
-- Gestion de Stock de Téléphones
-- =====================================================

-- Création de la base de données (à exécuter séparément si nécessaire)
-- CREATE DATABASE phone_stock_db;

-- =====================================================
-- Table des utilisateurs
-- =====================================================
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- Table des marques
-- =====================================================
CREATE TABLE IF NOT EXISTS brands (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- Table des téléphones
-- =====================================================
CREATE TABLE IF NOT EXISTS phones (
    id SERIAL PRIMARY KEY,
    brand_id INTEGER REFERENCES brands(id) ON DELETE SET NULL,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    model VARCHAR(100) NOT NULL,
    barcode VARCHAR(100),
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    quantity INTEGER DEFAULT 0,
    min_stock INTEGER DEFAULT 5,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- Table des mouvements de stock
-- =====================================================
CREATE TABLE IF NOT EXISTS stock_movements (
    id SERIAL PRIMARY KEY,
    phone_id INTEGER REFERENCES phones(id) ON DELETE CASCADE,
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    type VARCHAR(10) CHECK (type IN ('IN', 'OUT')) NOT NULL,
    quantity INTEGER NOT NULL,
    reason VARCHAR(255),
    status VARCHAR(20) DEFAULT 'confirme' CHECK (status IN ('en_attente', 'confirme', 'annule')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- Table de l'historique des prix
-- =====================================================
CREATE TABLE IF NOT EXISTS price_history (
    id SERIAL PRIMARY KEY,
    phone_id INTEGER REFERENCES phones(id) ON DELETE CASCADE,
    old_price DECIMAL(10,2),
    new_price DECIMAL(10,2) NOT NULL,
    changed_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- Index pour améliorer les performances
-- =====================================================
CREATE INDEX IF NOT EXISTS idx_phones_brand ON phones(brand_id);
CREATE INDEX IF NOT EXISTS idx_phones_quantity ON phones(quantity);
CREATE INDEX IF NOT EXISTS idx_phones_user ON phones(user_id);
CREATE INDEX IF NOT EXISTS idx_movements_phone ON stock_movements(phone_id);
CREATE INDEX IF NOT EXISTS idx_movements_date ON stock_movements(created_at);
CREATE INDEX IF NOT EXISTS idx_price_history_phone ON price_history(phone_id);

-- =====================================================
-- Table des partenariats
-- =====================================================
CREATE TABLE IF NOT EXISTS partnerships (
    id SERIAL PRIMARY KEY,
    requester_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    receiver_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'accepted', 'rejected')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(requester_id, receiver_id)
);
CREATE INDEX IF NOT EXISTS idx_partnerships_users ON partnerships(requester_id, receiver_id);

-- =====================================================
-- Table des clients
-- =====================================================
CREATE TABLE IF NOT EXISTS clients (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    name VARCHAR(150) NOT NULL,
    phone VARCHAR(50),
    email VARCHAR(100),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_clients_user ON clients(user_id);

-- =====================================================
-- Table des factures
-- =====================================================
CREATE TABLE IF NOT EXISTS invoices (
    id SERIAL PRIMARY KEY,
    invoice_number VARCHAR(30) UNIQUE NOT NULL,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    client_name VARCHAR(150) NOT NULL,
    client_phone VARCHAR(50),
    client_address TEXT,
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    notes TEXT,
    status VARCHAR(20) DEFAULT 'completed' CHECK (status IN ('completed', 'cancelled')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_invoices_user ON invoices(user_id);
CREATE INDEX IF NOT EXISTS idx_invoices_date ON invoices(created_at);

-- =====================================================
-- Table des lignes de facture
-- =====================================================
CREATE TABLE IF NOT EXISTS invoice_lines (
    id SERIAL PRIMARY KEY,
    invoice_id INTEGER NOT NULL REFERENCES invoices(id) ON DELETE CASCADE,
    phone_id INTEGER REFERENCES phones(id) ON DELETE SET NULL,
    phone_model VARCHAR(100),
    phone_brand VARCHAR(50),
    quantity INTEGER NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    line_total DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_invoice_lines_invoice ON invoice_lines(invoice_id);

-- =====================================================
-- Table des IMEI individuels par téléphone
-- =====================================================
CREATE TABLE IF NOT EXISTS phone_imeis (
    id SERIAL PRIMARY KEY,
    phone_id INTEGER NOT NULL REFERENCES phones(id) ON DELETE CASCADE,
    imei VARCHAR(100) UNIQUE NOT NULL,
    status VARCHAR(20) DEFAULT 'in_stock' CHECK (status IN ('in_stock', 'sold')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_phone_imeis_phone ON phone_imeis(phone_id);
CREATE INDEX IF NOT EXISTS idx_phone_imeis_status ON phone_imeis(phone_id, status);

-- =====================================================
-- Table de liaison IMEI / lignes de facture
-- =====================================================
CREATE TABLE IF NOT EXISTS invoice_line_imeis (
    id SERIAL PRIMARY KEY,
    invoice_line_id INTEGER NOT NULL REFERENCES invoice_lines(id) ON DELETE CASCADE,
    phone_imei_id INTEGER NOT NULL REFERENCES phone_imeis(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_invoice_line_imeis_line ON invoice_line_imeis(invoice_line_id);
CREATE INDEX IF NOT EXISTS idx_invoice_line_imeis_imei ON invoice_line_imeis(phone_imei_id);

-- =====================================================
-- Table de liaison IMEI / mouvements de stock (sorties)
-- =====================================================
CREATE TABLE IF NOT EXISTS stock_movement_imeis (
    id SERIAL PRIMARY KEY,
    movement_id INTEGER NOT NULL REFERENCES stock_movements(id) ON DELETE CASCADE,
    phone_imei_id INTEGER NOT NULL REFERENCES phone_imeis(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_stock_movement_imeis_movement ON stock_movement_imeis(movement_id);
CREATE INDEX IF NOT EXISTS idx_stock_movement_imeis_imei ON stock_movement_imeis(phone_imei_id);

-- =====================================================
-- Table des tentatives de login (rate limiting)
-- =====================================================
CREATE TABLE IF NOT EXISTS login_attempts (
    id SERIAL PRIMARY KEY,
    ip VARCHAR(45) NOT NULL,
    username VARCHAR(50) NOT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_login_attempts_ip ON login_attempts(ip, attempted_at);

-- =====================================================
-- Données initiales
-- =====================================================

-- Utilisateur admin par défaut (mot de passe: admin123)
INSERT INTO users (username, password, email) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com')
ON CONFLICT (username) DO NOTHING;

-- Marques de téléphones
INSERT INTO brands (name) VALUES
('Apple'),
('Samsung'),
('Xiaomi'),
('Huawei'),
('OnePlus'),
('Google'),
('Sony'),
('Oppo'),
('Vivo'),
('Nokia')
ON CONFLICT (name) DO NOTHING;

-- Quelques téléphones de démonstration (user_id = 1 pour l'admin)
INSERT INTO phones (brand_id, user_id, model, description, price, quantity, min_stock) VALUES
(1, 1, 'iPhone 15 Pro', 'Smartphone Apple dernière génération, puce A17 Pro', 1199.00, 25, 5),
(1, 1, 'iPhone 14', 'Smartphone Apple, excellent rapport qualité/prix', 899.00, 30, 10),
(2, 1, 'Galaxy S24 Ultra', 'Flagship Samsung avec S Pen intégré', 1299.00, 20, 5),
(2, 1, 'Galaxy A54', 'Milieu de gamme Samsung, très populaire', 449.00, 50, 15),
(3, 1, 'Xiaomi 14', 'Flagship Xiaomi avec optique Leica', 899.00, 15, 5),
(3, 1, 'Redmi Note 13 Pro', 'Excellent rapport qualité/prix', 299.00, 60, 20),
(4, 1, 'Huawei P60 Pro', 'Smartphone photo haut de gamme', 999.00, 10, 3),
(5, 1, 'OnePlus 12', 'Flagship killer, charge ultra rapide', 899.00, 18, 5),
(6, 1, 'Google Pixel 8 Pro', 'Meilleur smartphone pour la photo', 1099.00, 12, 5),
(7, 1, 'Sony Xperia 1 V', 'Smartphone pour créateurs de contenu', 1399.00, 8, 3)
ON CONFLICT DO NOTHING;
