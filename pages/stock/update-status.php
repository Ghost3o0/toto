<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /pages/stock/movements.php');
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $_SESSION['flash_message'] = 'Token de securite invalide.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /pages/stock/movements.php');
    exit;
}

$movementId = intval($_POST['movement_id'] ?? 0);
$newStatus = $_POST['new_status'] ?? '';

if (!$movementId || !in_array($newStatus, ['confirme', 'annule'])) {
    $_SESSION['flash_message'] = 'Parametres invalides.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /pages/stock/movements.php');
    exit;
}

// Retrieve the movement
$movement = fetchOne(
    "SELECT sm.*, p.user_id as phone_user_id FROM stock_movements sm LEFT JOIN phones p ON sm.phone_id = p.id WHERE sm.id = :id",
    ['id' => $movementId]
);

if (!$movement) {
    $_SESSION['flash_message'] = 'Mouvement introuvable.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /pages/stock/movements.php');
    exit;
}

// Only OUT movements with en_attente status can be changed
if ($movement['type'] !== 'OUT' || $movement['status'] !== 'en_attente') {
    $_SESSION['flash_message'] = 'Ce mouvement ne peut pas etre modifie.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /pages/stock/movements.php');
    exit;
}

// Check user has visibility on this phone
$visibleIds = getVisibleUserIds();
if (!in_array($movement['phone_user_id'], $visibleIds)) {
    $_SESSION['flash_message'] = 'Acces refuse.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /pages/stock/movements.php');
    exit;
}

$pdo = getConnection();
$pdo->beginTransaction();

try {
    if ($newStatus === 'confirme') {
        // Simply update the status - stock already decremented, IMEI already marked sold
        execute(
            "UPDATE stock_movements SET status = 'confirme' WHERE id = :id",
            ['id' => $movementId]
        );
        $_SESSION['flash_message'] = 'Mouvement confirme avec succes.';
        $_SESSION['flash_type'] = 'success';

    } elseif ($newStatus === 'annule') {
        // Update status
        execute(
            "UPDATE stock_movements SET status = 'annule' WHERE id = :id",
            ['id' => $movementId]
        );

        // Restore stock quantity
        execute(
            "UPDATE phones SET quantity = quantity + :qty, updated_at = CURRENT_TIMESTAMP WHERE id = :pid",
            ['qty' => $movement['quantity'], 'pid' => $movement['phone_id']]
        );

        // Restore IMEIs to in_stock using stock_movement_imeis
        execute(
            "UPDATE phone_imeis SET status = 'in_stock' WHERE id IN (SELECT phone_imei_id FROM stock_movement_imeis WHERE movement_id = :mid)",
            ['mid' => $movementId]
        );

        $_SESSION['flash_message'] = 'Mouvement annule. Le stock et les IMEI ont ete restaures.';
        $_SESSION['flash_type'] = 'success';
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['flash_message'] = 'Erreur : ' . $e->getMessage();
    $_SESSION['flash_type'] = 'danger';
}

header('Location: /pages/stock/movements.php');
exit;
