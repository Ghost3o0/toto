<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /pages/partners/list.php');
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $_SESSION['flash_message'] = 'Token de sécurité invalide.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /pages/partners/list.php');
    exit;
}

$userId = $_SESSION['user_id'];
$id = intval($_POST['id'] ?? 0);

if (!$id) {
    header('Location: /pages/partners/list.php');
    exit;
}

// Vérifier que le partenariat existe et concerne l'utilisateur connecté
$partnership = fetchOne(
    "SELECT * FROM partnerships WHERE id = :id AND (requester_id = :uid OR receiver_id = :uid2) AND status = 'accepted'",
    ['id' => $id, 'uid' => $userId, 'uid2' => $userId]
);

if (!$partnership) {
    $_SESSION['flash_message'] = 'Partenariat introuvable.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /pages/partners/list.php');
    exit;
}

execute("DELETE FROM partnerships WHERE id = :id", ['id' => $id]);

$_SESSION['flash_message'] = 'Partenariat supprimé.';
$_SESSION['flash_type'] = 'success';
header('Location: /pages/partners/list.php');
exit;
