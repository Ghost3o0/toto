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
    model VARCHAR(100) NOT NULL,
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- Index pour améliorer les performances
-- =====================================================
CREATE INDEX IF NOT EXISTS idx_phones_brand ON phones(brand_id);
CREATE INDEX IF NOT EXISTS idx_phones_quantity ON phones(quantity);
CREATE INDEX IF NOT EXISTS idx_movements_phone ON stock_movements(phone_id);
CREATE INDEX IF NOT EXISTS idx_movements_date ON stock_movements(created_at);

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

-- Quelques téléphones de démonstration
INSERT INTO phones (brand_id, model, description, price, quantity, min_stock) VALUES
(1, 'iPhone 15 Pro', 'Smartphone Apple dernière génération, puce A17 Pro', 1199.00, 25, 5),
(1, 'iPhone 14', 'Smartphone Apple, excellent rapport qualité/prix', 899.00, 30, 10),
(2, 'Galaxy S24 Ultra', 'Flagship Samsung avec S Pen intégré', 1299.00, 20, 5),
(2, 'Galaxy A54', 'Milieu de gamme Samsung, très populaire', 449.00, 50, 15),
(3, 'Xiaomi 14', 'Flagship Xiaomi avec optique Leica', 899.00, 15, 5),
(3, 'Redmi Note 13 Pro', 'Excellent rapport qualité/prix', 299.00, 60, 20),
(4, 'Huawei P60 Pro', 'Smartphone photo haut de gamme', 999.00, 10, 3),
(5, 'OnePlus 12', 'Flagship killer, charge ultra rapide', 899.00, 18, 5),
(6, 'Google Pixel 8 Pro', 'Meilleur smartphone pour la photo', 1099.00, 12, 5),
(7, 'Sony Xperia 1 V', 'Smartphone pour créateurs de contenu', 1399.00, 8, 3)
ON CONFLICT DO NOTHING;
