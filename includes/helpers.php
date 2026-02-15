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
         ORDER BY invoice_number DESC LIMIT 1",
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
