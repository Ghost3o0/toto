<?php
/**
 * Configuration de la connexion à PostgreSQL
 */

// Paramètres de connexion - À MODIFIER selon votre configuration
define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_NAME', 'phone_stock_db');
define('DB_USER', 'postgres');
define('DB_PASS', '12345678');

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
 * @param string $sql Requête SQL
 * @param array $params Paramètres de la requête
 * @return array Résultats
 */
function fetchAll(string $sql, array $params = []): array {
    $stmt = getConnection()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Exécute une requête SELECT et retourne une seule ligne
 * @param string $sql Requête SQL
 * @param array $params Paramètres de la requête
 * @return array|false Résultat ou false
 */
function fetchOne(string $sql, array $params = []): array|false {
    $stmt = getConnection()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}

/**
 * Exécute une requête INSERT, UPDATE ou DELETE
 * @param string $sql Requête SQL
 * @param array $params Paramètres de la requête
 * @return int Nombre de lignes affectées
 */
function execute(string $sql, array $params = []): int {
    $stmt = getConnection()->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

/**
 * Retourne le dernier ID inséré
 * @return string Dernier ID
 */
function lastInsertId(): string {
    return getConnection()->lastInsertId();
}
