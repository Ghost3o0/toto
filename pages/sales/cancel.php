<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
requireLogin();

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header('Location: /pages/sales/list.php');
    exit;
}

$uf = buildVisibleUserFilter();
$params = array_merge($uf['params'], ['id' => $id]);

$invoice = fetchOne(
    "SELECT * FROM invoices WHERE id = :id AND user_id IN ({$uf['placeholders']}) AND status = 'completed'",
    $params
);

if (!$invoice) {
    $_SESSION['flash_message'] = 'Facture introuvable ou déjà annulée.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /pages/sales/list.php');
    exit;
}

execute(
    "UPDATE invoices SET status = 'cancelled' WHERE id = :id",
    ['id' => $id]
);

$_SESSION['flash_message'] = "Facture {$invoice['invoice_number']} annulée. Le stock n'a pas été modifié (ajustement manuel si nécessaire).";
$_SESSION['flash_type'] = 'warning';
header('Location: /pages/sales/list.php');
exit;
