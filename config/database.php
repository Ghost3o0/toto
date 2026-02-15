<?php
/**
 * Configuration de la connexion à PostgreSQL
 * Compatible avec Railway (DATABASE_URL) et configuration locale
 */

// Parser DATABASE_URL si disponible (format Railway)
$databaseUrl = getenv('DATABASE_URL');

if ($databaseUrl) {
    $dbParams = parse_url($databaseUrl);
    define('DB_HOST', $dbParams['host'] ?? 'localhost');
    define('DB_PORT', $dbParams['port'] ?? '5432');
    define('DB_NAME', ltrim($dbParams['path'] ?? '/railway', '/'));
    define('DB_USER', $dbParams['user'] ?? 'postgres');
    define('DB_PASS', $dbParams['pass'] ?? '');
} else {
    // Variables d'environnement séparées ou valeurs locales
    define('DB_HOST', getenv('PGHOST') ?: 'localhost');
    define('DB_PORT', getenv('PGPORT') ?: '5432');
    define('DB_NAME', getenv('PGDATABASE') ?: 'phone_stock_db');
    define('DB_USER', getenv('PGUSER') ?: 'postgres');
    define('DB_PASS', getenv('PGPASSWORD') ?: '12345678');
}

/**
 * Établit la connexion à la base de données
 * @return PDO Instance de connexion PDO
 */
function getConnection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = sprintf(
                "pgsql:host=%s;port=%s;dbname=%s",
                DB_HOST,
                DB_PORT,
                DB_NAME
            );

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

        } catch (PDOException $e) {
            die("Erreur de connexion à la base de données: " . $e->getMessage());
        }
    }

    return $pdo;
}

/**
 * Exécute une requête SELECT et retourne tous les résultats
 */
function fetchAll(string $sql, array $params = []): array {
    $stmt = getConnection()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Exécute une requête SELECT et retourne une seule ligne
 */
function fetchOne(string $sql, array $params = []): array|false {
    $stmt = getConnection()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}

/**
 * Exécute une requête INSERT, UPDATE ou DELETE
 */
function execute(string $sql, array $params = []): int {
    $stmt = getConnection()->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

/**
 * Retourne le dernier ID inséré
 */
function lastInsertId(): string {
    return getConnection()->lastInsertId();
}
