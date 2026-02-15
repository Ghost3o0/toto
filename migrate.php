<?php
require_once __DIR__ . '/config/database.php';

$secret = $_GET['key'] ?? '';
if ($secret !== 'run_migration_v3_imei_2026') {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

$action = $_GET['action'] ?? 'fix';

try {
    $pdo = getConnection();

    if ($action === 'check') {
        $tables = ['users', 'brands', 'phones', 'stock_movements', 'price_history', 'partnerships', 'clients', 'invoices', 'invoice_lines', 'phone_imeis', 'invoice_line_imeis'];
        foreach ($tables as $table) {
            $cols = fetchAll("SELECT column_name, data_type, character_maximum_length FROM information_schema.columns WHERE table_name = :t ORDER BY ordinal_position", ['t' => $table]);
            echo "\n=== $table ===\n";
            if (empty($cols)) { echo "(not found)\n"; continue; }
            foreach ($cols as $col) {
                echo "  {$col['column_name']}: {$col['data_type']}({$col['character_maximum_length']})\n";
            }
        }
        exit;
    }

    // Strategy: DROP and recreate all tables properly
    // Save existing user data first
    $existingUsers = [];
    try {
        $existingUsers = fetchAll("SELECT * FROM users");
    } catch (Exception $e) {}

    $existingBrands = [];
    try {
        $existingBrands = fetchAll("SELECT * FROM brands");
    } catch (Exception $e) {}

    $results = [];

    // Drop all tables in reverse dependency order
    $pdo->exec("DROP TABLE IF EXISTS invoice_line_imeis CASCADE");
    $pdo->exec("DROP TABLE IF EXISTS phone_imeis CASCADE");
    $pdo->exec("DROP TABLE IF EXISTS invoice_lines CASCADE");
    $pdo->exec("DROP TABLE IF EXISTS invoices CASCADE");
    $pdo->exec("DROP TABLE IF EXISTS clients CASCADE");
    $pdo->exec("DROP TABLE IF EXISTS partnerships CASCADE");
    $pdo->exec("DROP TABLE IF EXISTS price_history CASCADE");
    $pdo->exec("DROP TABLE IF EXISTS stock_movements CASCADE");
    $pdo->exec("DROP TABLE IF EXISTS phones CASCADE");
    $pdo->exec("DROP TABLE IF EXISTS brands CASCADE");
    $pdo->exec("DROP TABLE IF EXISTS users CASCADE");
    $results[] = 'tables dropped';

    // Recreate all tables with correct types
    $pdo->exec("CREATE TABLE users (
        id SERIAL PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE brands (
        id SERIAL PRIMARY KEY,
        name VARCHAR(50) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE phones (
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
    )");

    $pdo->exec("CREATE TABLE stock_movements (
        id SERIAL PRIMARY KEY,
        phone_id INTEGER REFERENCES phones(id) ON DELETE CASCADE,
        user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
        type VARCHAR(10) CHECK (type IN ('IN', 'OUT')) NOT NULL,
        quantity INTEGER NOT NULL,
        reason VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE price_history (
        id SERIAL PRIMARY KEY,
        phone_id INTEGER REFERENCES phones(id) ON DELETE CASCADE,
        old_price DECIMAL(10,2),
        new_price DECIMAL(10,2) NOT NULL,
        changed_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE partnerships (
        id SERIAL PRIMARY KEY,
        requester_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        receiver_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'accepted', 'rejected')),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(requester_id, receiver_id)
    )");

    $pdo->exec("CREATE TABLE clients (
        id SERIAL PRIMARY KEY,
        user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        name VARCHAR(150) NOT NULL,
        phone VARCHAR(50),
        email VARCHAR(100),
        address TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE invoices (
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
    )");

    $pdo->exec("CREATE TABLE invoice_lines (
        id SERIAL PRIMARY KEY,
        invoice_id INTEGER NOT NULL REFERENCES invoices(id) ON DELETE CASCADE,
        phone_id INTEGER REFERENCES phones(id) ON DELETE SET NULL,
        phone_model VARCHAR(100),
        phone_brand VARCHAR(50),
        quantity INTEGER NOT NULL,
        unit_price DECIMAL(10,2) NOT NULL,
        line_total DECIMAL(12,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE phone_imeis (
        id SERIAL PRIMARY KEY,
        phone_id INTEGER NOT NULL REFERENCES phones(id) ON DELETE CASCADE,
        imei VARCHAR(100) UNIQUE NOT NULL,
        status VARCHAR(20) DEFAULT 'in_stock' CHECK (status IN ('in_stock', 'sold')),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE invoice_line_imeis (
        id SERIAL PRIMARY KEY,
        invoice_line_id INTEGER NOT NULL REFERENCES invoice_lines(id) ON DELETE CASCADE,
        phone_imei_id INTEGER NOT NULL REFERENCES phone_imeis(id) ON DELETE CASCADE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $results[] = 'tables created';

    // Create indexes
    $pdo->exec('CREATE INDEX idx_phones_brand ON phones(brand_id)');
    $pdo->exec('CREATE INDEX idx_phones_quantity ON phones(quantity)');
    $pdo->exec('CREATE INDEX idx_phones_user ON phones(user_id)');
    $pdo->exec('CREATE INDEX idx_movements_phone ON stock_movements(phone_id)');
    $pdo->exec('CREATE INDEX idx_movements_date ON stock_movements(created_at)');
    $pdo->exec('CREATE INDEX idx_price_history_phone ON price_history(phone_id)');
    $pdo->exec('CREATE INDEX idx_partnerships_users ON partnerships(requester_id, receiver_id)');
    $pdo->exec('CREATE INDEX idx_clients_user ON clients(user_id)');
    $pdo->exec('CREATE INDEX idx_invoices_user ON invoices(user_id)');
    $pdo->exec('CREATE INDEX idx_invoices_date ON invoices(created_at)');
    $pdo->exec('CREATE INDEX idx_invoice_lines_invoice ON invoice_lines(invoice_id)');
    $pdo->exec('CREATE INDEX idx_phone_imeis_phone ON phone_imeis(phone_id)');
    $pdo->exec('CREATE INDEX idx_phone_imeis_status ON phone_imeis(phone_id, status)');
    $pdo->exec('CREATE INDEX idx_invoice_line_imeis_line ON invoice_line_imeis(invoice_line_id)');
    $pdo->exec('CREATE INDEX idx_invoice_line_imeis_imei ON invoice_line_imeis(phone_imei_id)');
    $results[] = 'indexes created';

    // Insert default data
    $pdo->exec("INSERT INTO users (username, password, email) VALUES
        ('admin', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com')
        ON CONFLICT (username) DO NOTHING");

    $pdo->exec("INSERT INTO brands (name) VALUES
        ('Apple'),('Samsung'),('Xiaomi'),('Huawei'),('OnePlus'),('Google'),('Sony'),('Oppo'),('Vivo'),('Nokia')
        ON CONFLICT (name) DO NOTHING");
    $results[] = 'default data inserted';

    echo "Migration completed:\n" . implode("\n", $results);
} catch (Exception $e) {
    http_response_code(500);
    echo 'Error: ' . $e->getMessage();
}
