<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
requireLogin();

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header('Location: /pages/phones/list.php');
    exit;
}

// Vérifier que le téléphone existe et appartient aux utilisateurs visibles
$visibleIds = getVisibleUserIds();
$phone = fetchOne("SELECT * FROM phones WHERE id = :id", ['id' => $id]);
if (!$phone || !in_array($phone['user_id'], $visibleIds)) {
    $_SESSION['flash_message'] = 'Téléphone introuvable.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /pages/phones/list.php');
    exit;
}

// Vérifier qu'il n'y a pas d'IMEI vendus liés à ce téléphone
$soldImeis = fetchOne(
    "SELECT COUNT(*) as cnt FROM phone_imeis WHERE phone_id = :id AND status = 'sold'",
    ['id' => $id]
);
if ($soldImeis && $soldImeis['cnt'] > 0) {
    $_SESSION['flash_message'] = 'Impossible de supprimer ce téléphone : ' . $soldImeis['cnt'] . ' IMEI(s) sont encore liés à des ventes.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /pages/phones/list.php');
    exit;
}

// Vérifier qu'il n'y a pas de mouvements de stock en attente
$pendingMovements = fetchOne(
    "SELECT COUNT(*) as cnt FROM stock_movements WHERE phone_id = :id AND status = 'en_attente'",
    ['id' => $id]
);
if ($pendingMovements && $pendingMovements['cnt'] > 0) {
    $_SESSION['flash_message'] = 'Impossible de supprimer ce téléphone : ' . $pendingMovements['cnt'] . ' mouvement(s) de stock en attente.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /pages/phones/list.php');
    exit;
}

// Supprimer le téléphone
execute("DELETE FROM phones WHERE id = :id", ['id' => $id]);

$_SESSION['flash_message'] = 'Téléphone "' . $phone['model'] . '" supprimé avec succès.';
$_SESSION['flash_type'] = 'success';
header('Location: /pages/phones/list.php');
exit;
