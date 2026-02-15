<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo = getConnection();
    $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (id SERIAL PRIMARY KEY, ip VARCHAR(45) NOT NULL, username VARCHAR(50) NOT NULL, attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_login_attempts_ip ON login_attempts(ip, attempted_at)");
    echo "OK - Table login_attempts creee avec succes.";
} catch (Exception $e) {
    echo "ERREUR: " . $e->getMessage();
}
