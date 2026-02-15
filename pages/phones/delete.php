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

// Supprimer le téléphone
execute("DELETE FROM phones WHERE id = :id", ['id' => $id]);

$_SESSION['flash_message'] = 'Téléphone "' . $phone['model'] . '" supprimé avec succès.';
$_SESSION['flash_type'] = 'success';
header('Location: /pages/phones/list.php');
exit;
