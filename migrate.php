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

    $pdo->exec("CREATE TABLE IF NOT EXISTS phone_imeis (
        id SERIAL PRIMARY KEY,
        phone_id INTEGER NOT NULL REFERENCES phones(id) ON DELETE CASCADE,
        imei VARCHAR(100) UNIQUE NOT NULL,
        status VARCHAR(20) DEFAULT 'in_stock' CHECK (status IN ('in_stock', 'sold')),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_phone_imeis_phone ON phone_imeis(phone_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_phone_imeis_status ON phone_imeis(phone_id, status)');

    $pdo->exec("CREATE TABLE IF NOT EXISTS invoice_line_imeis (
        id SERIAL PRIMARY KEY,
        invoice_line_id INTEGER NOT NULL REFERENCES invoice_lines(id) ON DELETE CASCADE,
        phone_imei_id INTEGER NOT NULL REFERENCES phone_imeis(id) ON DELETE CASCADE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_invoice_line_imeis_line ON invoice_line_imeis(invoice_line_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_invoice_line_imeis_imei ON invoice_line_imeis(phone_imei_id)');

    echo 'Migration v3 IMEI completed successfully!';
} catch (Exception $e) {
    http_response_code(500);
    echo 'Error: ' . $e->getMessage();
}
