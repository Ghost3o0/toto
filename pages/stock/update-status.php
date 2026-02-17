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
        // Update the movement status
        execute(
            "UPDATE stock_movements SET status = 'confirme' WHERE id = :id",
            ['id' => $movementId]
        );

        // Fetch phone info for invoice
        $phone = fetchOne(
            "SELECT p.model, p.price, b.name as brand_name
             FROM phones p
             LEFT JOIN brands b ON p.brand_id = b.id
             WHERE p.id = :id",
            ['id' => $movement['phone_id']]
        );

        // Fetch IMEIs linked to this movement
        $movementImeis = fetchAll(
            "SELECT phone_imei_id FROM stock_movement_imeis WHERE movement_id = :mid",
            ['mid' => $movementId]
        );

        // Generate invoice
        $invoiceNumber = generateInvoiceNumber();
        $clientName = trim($movement['reason'] ?? '') ?: 'Client';
        $unitPrice = $phone['price'] ?? 0;
        $totalAmount = $unitPrice * $movement['quantity'];

        execute(
            "INSERT INTO invoices (invoice_number, user_id, client_name, total_amount)
             VALUES (:num, :uid, :name, :total)",
            [
                'num' => $invoiceNumber,
                'uid' => $movement['user_id'],
                'name' => $clientName,
                'total' => $totalAmount
            ]
        );
        $invoiceId = lastInsertId();

        // Create invoice line
        execute(
            "INSERT INTO invoice_lines (invoice_id, phone_id, phone_model, phone_brand, quantity, unit_price, line_total)
             VALUES (:iid, :pid, :model, :brand, :qty, :price, :total)",
            [
                'iid' => $invoiceId,
                'pid' => $movement['phone_id'],
                'model' => $phone['model'] ?? '',
                'brand' => $phone['brand_name'] ?? '',
                'qty' => $movement['quantity'],
                'price' => $unitPrice,
                'total' => $totalAmount
            ]
        );
        $invoiceLineId = lastInsertId();

        // Link IMEIs to invoice line
        foreach ($movementImeis as $mi) {
            execute(
                "INSERT INTO invoice_line_imeis (invoice_line_id, phone_imei_id) VALUES (:line_id, :imei_id)",
                ['line_id' => $invoiceLineId, 'imei_id' => $mi['phone_imei_id']]
            );
        }

        $_SESSION['flash_message'] = "Mouvement confirme. Facture $invoiceNumber creee.";
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
