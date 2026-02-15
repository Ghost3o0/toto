<?php
/**
 * Page de diagnostic - À SUPPRIMER après déploiement
 */

echo "<h1>Diagnostic PhoneStock</h1>";

echo "<h2>1. Version PHP</h2>";
echo "<p>" . phpversion() . "</p>";

echo "<h2>2. Extensions PDO chargées</h2>";
echo "<pre>";
print_r(PDO::getAvailableDrivers());
echo "</pre>";

echo "<h2>3. Extension pdo_pgsql</h2>";
if (extension_loaded('pdo_pgsql')) {
    echo "<p style='color:green'>✓ pdo_pgsql est installé</p>";
} else {
    echo "<p style='color:red'>✗ pdo_pgsql N'EST PAS installé</p>";
}

echo "<h2>4. Extension pgsql</h2>";
if (extension_loaded('pgsql')) {
    echo "<p style='color:green'>✓ pgsql est installé</p>";
} else {
    echo "<p style='color:red'>✗ pgsql N'EST PAS installé</p>";
}

echo "<h2>5. Variables d'environnement PostgreSQL</h2>";
echo "<ul>";
echo "<li>PGHOST: " . (getenv('PGHOST') ? '✓ défini (' . getenv('PGHOST') . ')' : '✗ non défini') . "</li>";
echo "<li>PGPORT: " . (getenv('PGPORT') ? '✓ défini (' . getenv('PGPORT') . ')' : '✗ non défini') . "</li>";
echo "<li>PGDATABASE: " . (getenv('PGDATABASE') ? '✓ défini (' . getenv('PGDATABASE') . ')' : '✗ non défini') . "</li>";
echo "<li>PGUSER: " . (getenv('PGUSER') ? '✓ défini (' . getenv('PGUSER') . ')' : '✗ non défini') . "</li>";
echo "<li>PGPASSWORD: " . (getenv('PGPASSWORD') ? '✓ défini (masqué)' : '✗ non défini') . "</li>";
echo "<li>DATABASE_URL: " . (getenv('DATABASE_URL') ? '✓ défini' : '✗ non défini') . "</li>";
echo "</ul>";

echo "<h2>6. Test de connexion</h2>";
try {
    $host = getenv('PGHOST') ?: 'localhost';
    $port = getenv('PGPORT') ?: '5432';
    $dbname = getenv('PGDATABASE') ?: 'phone_stock_db';
    $user = getenv('PGUSER') ?: 'postgres';
    $pass = getenv('PGPASSWORD') ?: '';

    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $pass);
    echo "<p style='color:green'>✓ Connexion réussie!</p>";

    $result = $pdo->query("SELECT version()");
    echo "<p>PostgreSQL: " . $result->fetchColumn() . "</p>";
} catch (PDOException $e) {
    echo "<p style='color:red'>✗ Erreur: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h2>7. Toutes les extensions PHP</h2>";
echo "<pre>" . implode(", ", get_loaded_extensions()) . "</pre>";
