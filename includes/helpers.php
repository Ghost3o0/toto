<?php
/**
 * Fonctions utilitaires partagées
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Retourne les IDs des utilisateurs dont le stock est visible pour l'utilisateur connecté
 * (lui-même + ses partenaires acceptés)
 * @return array Liste d'IDs utilisateurs
 */
function getVisibleUserIds(): array {
    $userId = $_SESSION['user_id'];

    $partners = fetchAll(
        "SELECT CASE WHEN requester_id = :uid THEN receiver_id ELSE requester_id END as partner_id
         FROM partnerships
         WHERE (requester_id = :uid2 OR receiver_id = :uid3) AND status = 'accepted'",
        ['uid' => $userId, 'uid2' => $userId, 'uid3' => $userId]
    );

    $ids = [$userId];
    foreach ($partners as $p) {
        $ids[] = (int)$p['partner_id'];
    }
    return $ids;
}

/**
 * Génère une clause SQL IN avec des placeholders nommés pour les IDs visibles
 * @return array ['placeholders' => ':uid0,:uid1,...', 'params' => ['uid0' => 1, 'uid1' => 2, ...]]
 */
function buildVisibleUserFilter(): array {
    $ids = getVisibleUserIds();
    $placeholders = [];
    $params = [];
    foreach ($ids as $i => $id) {
        $key = "vuid$i";
        $placeholders[] = ":$key";
        $params[$key] = $id;
    }
    return [
        'placeholders' => implode(',', $placeholders),
        'params' => $params,
        'ids' => $ids
    ];
}

/**
 * Génère le prochain numéro de facture au format FAC-YYYY-NNNNNN
 * @return string
 */
function generateInvoiceNumber(): string {
    $year = date('Y');
    $last = fetchOne(
        "SELECT invoice_number FROM invoices
         WHERE invoice_number LIKE :prefix
         ORDER BY invoice_number DESC LIMIT 1
         FOR UPDATE",
        ['prefix' => "FAC-$year-%"]
    );

    if ($last) {
        $lastNum = (int)substr($last['invoice_number'], -6);
        $nextNum = $lastNum + 1;
    } else {
        $nextNum = 1;
    }

    return sprintf("FAC-%s-%06d", $year, $nextNum);
}

/**
 * Vérifie si une table existe dans le schéma public
 * @param string $table Nom de la table
 * @return bool
 */
function tableExists(string $table): bool {
    $row = fetchOne("SELECT to_regclass(:name) as reg", ['name' => 'public.' . $table]);
    return !empty($row) && !empty($row['reg']);
}

/**
 * Vérifie si une colonne existe dans une table
 * @param string $table Nom de la table
 * @param string $column Nom de la colonne
 * @return bool
 */
function columnExists(string $table, string $column): bool {
    $row = fetchOne(
        "SELECT 1 FROM information_schema.columns WHERE table_name = :table AND column_name = :column",
        ['table' => $table, 'column' => $column]
    );
    return !empty($row);
}

/**
 * Auto-migration : ajoute la colonne status à stock_movements et crée stock_movement_imeis
 */
function runAutoMigrations(): void {
    if (!columnExists('stock_movements', 'status')) {
        getConnection()->exec(
            "ALTER TABLE stock_movements ADD COLUMN status VARCHAR(20) DEFAULT 'confirme' CHECK (status IN ('en_attente', 'confirme', 'annule'))"
        );
    }
    if (!tableExists('stock_movement_imeis')) {
        getConnection()->exec(
            "CREATE TABLE stock_movement_imeis (
                id SERIAL PRIMARY KEY,
                movement_id INTEGER NOT NULL REFERENCES stock_movements(id) ON DELETE CASCADE,
                phone_imei_id INTEGER NOT NULL REFERENCES phone_imeis(id) ON DELETE CASCADE
            )"
        );
    }
}

// Exécuter les auto-migrations au chargement
runAutoMigrations();

/**
 * Valide un IMEI : 15 chiffres + checksum Luhn
 * @param string $imei IMEI à valider
 * @return bool
 */
function validateImei(string $imei): bool {
    // Doit être exactement 15 chiffres
    if (!preg_match('/^\d{15}$/', $imei)) {
        return false;
    }

    // Vérification Luhn
    $sum = 0;
    for ($i = 0; $i < 15; $i++) {
        $digit = (int)$imei[$i];
        if ($i % 2 === 1) {
            $digit *= 2;
            if ($digit > 9) {
                $digit -= 9;
            }
        }
        $sum += $digit;
    }

    return $sum % 10 === 0;
}
