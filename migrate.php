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

    // Fix users table columns
    $pdo->exec("ALTER TABLE users ALTER COLUMN username TYPE VARCHAR(50)");
    $pdo->exec("ALTER TABLE users ALTER COLUMN password TYPE VARCHAR(255)");
    $pdo->exec("ALTER TABLE users ALTER COLUMN email TYPE VARCHAR(100)");
    $pdo->exec("ALTER TABLE users ALTER COLUMN created_at TYPE TIMESTAMP USING created_at::timestamp");
    $results[] = 'users fixed';

    // Check and fix brands table
    $cols = fetchAll("SELECT column_name, data_type, character_maximum_length FROM information_schema.columns WHERE table_name = 'brands' ORDER BY ordinal_position");
    $brandCols = array_column($cols, 'data_type', 'column_name');
    if (isset($brandCols['name']) && $brandCols['name'] === 'character') {
        $pdo->exec("ALTER TABLE brands ALTER COLUMN name TYPE VARCHAR(50)");
        $results[] = 'brands fixed';
    } else {
        $results[] = 'brands OK';
    }

    // Check and fix phones table
    $cols = fetchAll("SELECT column_name, data_type, character_maximum_length FROM information_schema.columns WHERE table_name = 'phones' ORDER BY ordinal_position");
    $phoneCols = array_column($cols, 'data_type', 'column_name');
    if (isset($phoneCols['model']) && $phoneCols['model'] === 'character') {
        $pdo->exec("ALTER TABLE phones ALTER COLUMN model TYPE VARCHAR(100)");
        $pdo->exec("ALTER TABLE phones ALTER COLUMN barcode TYPE VARCHAR(100)");
        $pdo->exec("ALTER TABLE phones ALTER COLUMN description TYPE TEXT");
        $pdo->exec("ALTER TABLE phones ALTER COLUMN created_at TYPE TIMESTAMP USING created_at::timestamp");
        $pdo->exec("ALTER TABLE phones ALTER COLUMN updated_at TYPE TIMESTAMP USING updated_at::timestamp");
        $results[] = 'phones fixed';
    } else {
        $results[] = 'phones OK';
    }

    // Check and fix stock_movements table
    $cols = fetchAll("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'stock_movements' ORDER BY ordinal_position");
    $smCols = array_column($cols, 'data_type', 'column_name');
    if (isset($smCols['type']) && $smCols['type'] === 'character') {
        $pdo->exec("ALTER TABLE stock_movements ALTER COLUMN type TYPE VARCHAR(10)");
        $pdo->exec("ALTER TABLE stock_movements ALTER COLUMN reason TYPE VARCHAR(255)");
        $pdo->exec("ALTER TABLE stock_movements ALTER COLUMN created_at TYPE TIMESTAMP USING created_at::timestamp");
        $results[] = 'stock_movements fixed';
    } else {
        $results[] = 'stock_movements OK';
    }

    // Check and fix price_history table
    $cols = fetchAll("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'price_history' ORDER BY ordinal_position");
    $phCols = array_column($cols, 'data_type', 'column_name');
    if (isset($phCols['created_at']) && $phCols['created_at'] === 'date') {
        $pdo->exec("ALTER TABLE price_history ALTER COLUMN created_at TYPE TIMESTAMP USING created_at::timestamp");
        $results[] = 'price_history fixed';
    } else {
        $results[] = 'price_history OK';
    }

    // Check and fix partnerships table
    $cols = fetchAll("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'partnerships' ORDER BY ordinal_position");
    $pCols = array_column($cols, 'data_type', 'column_name');
    if (isset($pCols['status']) && $pCols['status'] === 'character') {
        $pdo->exec("ALTER TABLE partnerships ALTER COLUMN status TYPE VARCHAR(20)");
        $pdo->exec("ALTER TABLE partnerships ALTER COLUMN created_at TYPE TIMESTAMP USING created_at::timestamp");
        $pdo->exec("ALTER TABLE partnerships ALTER COLUMN updated_at TYPE TIMESTAMP USING updated_at::timestamp");
        $results[] = 'partnerships fixed';
    } else {
        $results[] = 'partnerships OK';
    }

    // Check and fix clients table
    $cols = fetchAll("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'clients' ORDER BY ordinal_position");
    $cCols = array_column($cols, 'data_type', 'column_name');
    if (isset($cCols['name']) && $cCols['name'] === 'character') {
        $pdo->exec("ALTER TABLE clients ALTER COLUMN name TYPE VARCHAR(150)");
        $pdo->exec("ALTER TABLE clients ALTER COLUMN phone TYPE VARCHAR(50)");
        $pdo->exec("ALTER TABLE clients ALTER COLUMN email TYPE VARCHAR(100)");
        $pdo->exec("ALTER TABLE clients ALTER COLUMN address TYPE TEXT");
        $pdo->exec("ALTER TABLE clients ALTER COLUMN created_at TYPE TIMESTAMP USING created_at::timestamp");
        $results[] = 'clients fixed';
    } else {
        $results[] = 'clients OK';
    }

    // Check and fix invoices table
    $cols = fetchAll("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'invoices' ORDER BY ordinal_position");
    $iCols = array_column($cols, 'data_type', 'column_name');
    if (isset($iCols['invoice_number']) && $iCols['invoice_number'] === 'character') {
        $pdo->exec("ALTER TABLE invoices ALTER COLUMN invoice_number TYPE VARCHAR(30)");
        $pdo->exec("ALTER TABLE invoices ALTER COLUMN client_name TYPE VARCHAR(150)");
        $pdo->exec("ALTER TABLE invoices ALTER COLUMN client_phone TYPE VARCHAR(50)");
        $pdo->exec("ALTER TABLE invoices ALTER COLUMN client_address TYPE TEXT");
        $pdo->exec("ALTER TABLE invoices ALTER COLUMN notes TYPE TEXT");
        $pdo->exec("ALTER TABLE invoices ALTER COLUMN status TYPE VARCHAR(20)");
        $pdo->exec("ALTER TABLE invoices ALTER COLUMN created_at TYPE TIMESTAMP USING created_at::timestamp");
        $results[] = 'invoices fixed';
    } else {
        $results[] = 'invoices OK';
    }

    // Check and fix invoice_lines table
    $cols = fetchAll("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'invoice_lines' ORDER BY ordinal_position");
    $ilCols = array_column($cols, 'data_type', 'column_name');
    if (isset($ilCols['phone_model']) && $ilCols['phone_model'] === 'character') {
        $pdo->exec("ALTER TABLE invoice_lines ALTER COLUMN phone_model TYPE VARCHAR(100)");
        $pdo->exec("ALTER TABLE invoice_lines ALTER COLUMN phone_brand TYPE VARCHAR(50)");
        $pdo->exec("ALTER TABLE invoice_lines ALTER COLUMN created_at TYPE TIMESTAMP USING created_at::timestamp");
        $results[] = 'invoice_lines fixed';
    } else {
        $results[] = 'invoice_lines OK';
    }

    // Check and fix phone_imeis table
    $cols = fetchAll("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'phone_imeis' ORDER BY ordinal_position");
    $piCols = array_column($cols, 'data_type', 'column_name');
    if (isset($piCols['imei']) && $piCols['imei'] === 'character') {
        $pdo->exec("ALTER TABLE phone_imeis ALTER COLUMN imei TYPE VARCHAR(100)");
        $pdo->exec("ALTER TABLE phone_imeis ALTER COLUMN status TYPE VARCHAR(20)");
        $pdo->exec("ALTER TABLE phone_imeis ALTER COLUMN created_at TYPE TIMESTAMP USING created_at::timestamp");
        $results[] = 'phone_imeis fixed';
    } else {
        $results[] = 'phone_imeis OK';
    }

    // Check and fix invoice_line_imeis table
    $cols = fetchAll("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'invoice_line_imeis' ORDER BY ordinal_position");
    $iliCols = array_column($cols, 'data_type', 'column_name');
    if (isset($iliCols['created_at']) && $iliCols['created_at'] === 'date') {
        $pdo->exec("ALTER TABLE invoice_line_imeis ALTER COLUMN created_at TYPE TIMESTAMP USING created_at::timestamp");
        $results[] = 'invoice_line_imeis fixed';
    } else {
        $results[] = 'invoice_line_imeis OK';
    }

    // Re-insert default data
    $pdo->exec("INSERT INTO users (username, password, email) VALUES
        ('admin', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com')
        ON CONFLICT (username) DO NOTHING");

    $pdo->exec("INSERT INTO brands (name) VALUES
        ('Apple'),('Samsung'),('Xiaomi'),('Huawei'),('OnePlus'),('Google'),('Sony'),('Oppo'),('Vivo'),('Nokia')
        ON CONFLICT (name) DO NOTHING");
    $results[] = 'default data OK';

    echo "Migration completed:\n" . implode("\n", $results);
} catch (Exception $e) {
    http_response_code(500);
    echo 'Error: ' . $e->getMessage();
}
