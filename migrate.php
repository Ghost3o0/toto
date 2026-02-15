<?php
/**
 * Migration : création de la table login_attempts
 * Exécuter une fois en prod, puis supprimer ce fichier.
 *
 * Usage : php migrate.php
 */

require_once __DIR__ . '/config/database.php';

try {
    execute("
        CREATE TABLE IF NOT EXISTS login_attempts (
            id SERIAL PRIMARY KEY,
            ip VARCHAR(45) NOT NULL,
            username VARCHAR(50) NOT NULL,
            attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    execute("CREATE INDEX IF NOT EXISTS idx_login_attempts_ip ON login_attempts(ip, attempted_at)");

    echo "Migration réussie : table login_attempts créée.\n";
} catch (Exception $e) {
    echo "Erreur de migration : " . $e->getMessage() . "\n";
    exit(1);
}
