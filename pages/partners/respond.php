<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireLogin();

$userId = $_SESSION['user_id'];
$id = intval($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

if (!$id || !in_array($action, ['accept', 'reject'])) {
    header('Location: /pages/partners/list.php');
    exit;
}

// Vérifier que la demande existe et est destinée à l'utilisateur connecté
$partnership = fetchOne(
    "SELECT * FROM partnerships WHERE id = :id AND receiver_id = :uid AND status = 'pending'",
    ['id' => $id, 'uid' => $userId]
);

if (!$partnership) {
    $_SESSION['flash_message'] = 'Demande de partenariat introuvable.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /pages/partners/list.php');
    exit;
}

$newStatus = $action === 'accept' ? 'accepted' : 'rejected';
execute(
    "UPDATE partnerships SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id",
    ['status' => $newStatus, 'id' => $id]
);

if ($action === 'accept') {
    $_SESSION['flash_message'] = 'Partenariat accepté ! Vous pouvez maintenant voir le stock de votre partenaire.';
    $_SESSION['flash_type'] = 'success';
} else {
    $_SESSION['flash_message'] = 'Demande de partenariat refusée.';
    $_SESSION['flash_type'] = 'info';
}

header('Location: /pages/partners/list.php');
exit;
