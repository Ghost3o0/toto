<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

$_SESSION['last_activity'] = time();

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID manquant']);
    exit;
}

// Vérifier que le téléphone existe et est visible par l'utilisateur
$visibleIds = getVisibleUserIds();
$phone = fetchOne("SELECT id, user_id FROM phones WHERE id = :id", ['id' => $id]);

if (!$phone || !in_array($phone['user_id'], $visibleIds)) {
    http_response_code(404);
    echo json_encode(['error' => 'Téléphone introuvable']);
    exit;
}

// Récupérer les IMEI
$imeis = fetchAll(
    "SELECT id, imei, status, created_at FROM phone_imeis WHERE phone_id = :id ORDER BY created_at",
    ['id' => $id]
);

echo json_encode(['imeis' => $imeis]);
