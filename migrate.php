<?php
require_once __DIR__ . '/config/database.php';

$secret = $_GET['key'] ?? '';
if ($secret !== 'run_migration_v3_imei_2026') {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

try {
    $pdo = getConnection();
    $results = [];

    // Partnerships
    $pdo->exec("CREATE TABLE IF NOT EXISTS partnerships (
        id SERIAL PRIMARY KEY,
        requester_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        receiver_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'accepted', 'rejected')),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(requester_id, receiver_id)
    )");
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_partnerships_users ON partnerships(requester_id, receiver_id)');
    $results[] = 'partnerships OK';

    // Clients
    $pdo->exec("CREATE TABLE IF NOT EXISTS clients (
        id SERIAL PRIMARY KEY,
        user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        name VARCHAR(150) NOT NULL,
        phone VARCHAR(50),
        email VARCHAR(100),
        address TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_clients_user ON clients(user_id)');
    $results[] = 'clients OK';

    // Invoices
    $pdo->exec("CREATE TABLE IF NOT EXISTS invoices (
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
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_invoices_user ON invoices(user_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_invoices_date ON invoices(created_at)');
    $results[] = 'invoices OK';

    // Invoice lines
    $pdo->exec("CREATE TABLE IF NOT EXISTS invoice_lines (
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
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_invoice_lines_invoice ON invoice_lines(invoice_id)');
    $results[] = 'invoice_lines OK';

    // Phone IMEIs
    $pdo->exec("CREATE TABLE IF NOT EXISTS phone_imeis (
        id SERIAL PRIMARY KEY,
        phone_id INTEGER NOT NULL REFERENCES phones(id) ON DELETE CASCADE,
        imei VARCHAR(100) UNIQUE NOT NULL,
        status VARCHAR(20) DEFAULT 'in_stock' CHECK (status IN ('in_stock', 'sold')),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_phone_imeis_phone ON phone_imeis(phone_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_phone_imeis_status ON phone_imeis(phone_id, status)');
    $results[] = 'phone_imeis OK';

    // Invoice line IMEIs
    $pdo->exec("CREATE TABLE IF NOT EXISTS invoice_line_imeis (
        id SERIAL PRIMARY KEY,
        invoice_line_id INTEGER NOT NULL REFERENCES invoice_lines(id) ON DELETE CASCADE,
        phone_imei_id INTEGER NOT NULL REFERENCES phone_imeis(id) ON DELETE CASCADE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_invoice_line_imeis_line ON invoice_line_imeis(invoice_line_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_invoice_line_imeis_imei ON invoice_line_imeis(phone_imei_id)');
    $results[] = 'invoice_line_imeis OK';

    echo 'Migration completed: ' . implode(' | ', $results);
} catch (Exception $e) {
    http_response_code(500);
    echo 'Error: ' . $e->getMessage();
}
