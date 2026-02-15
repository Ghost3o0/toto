<?php
require_once __DIR__ . '/config/database.php';

$secret = $_GET['key'] ?? '';
if ($secret !== 'run_migration_v3_imei_2026') {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

$action = $_GET['action'] ?? 'check';

try {
    $pdo = getConnection();

    if ($action === 'check') {
        $cols = fetchAll("SELECT column_name, data_type, character_maximum_length FROM information_schema.columns WHERE table_name = 'users' ORDER BY ordinal_position");
        echo "Users table columns:\n";
        foreach ($cols as $col) {
            echo $col['column_name'] . ' - ' . $col['data_type'] . ' (' . ($col['character_maximum_length'] ?? 'null') . ")\n";
        }
    } elseif ($action === 'fix') {
        $pdo->exec("ALTER TABLE users ALTER COLUMN password TYPE VARCHAR(255)");
        echo "Fixed: password column changed to VARCHAR(255)";
    }
} catch (Exception $e) {
    http_response_code(500);
    echo 'Error: ' . $e->getMessage();
}
