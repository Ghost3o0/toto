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

$pdo = getConnection();
$pdo->beginTransaction();
try {
    // 1. Annuler la facture
    execute(
        "UPDATE invoices SET status = 'cancelled' WHERE id = :id",
        ['id' => $id]
    );

    // 2. Récupérer les lignes de la facture pour restaurer le stock et les IMEI
    $lines = fetchAll(
        "SELECT il.id as line_id, il.phone_id, il.quantity FROM invoice_lines il WHERE il.invoice_id = :id",
        ['id' => $id]
    );

    foreach ($lines as $line) {
        // 3. Restaurer les IMEI liés à cette ligne en 'in_stock'
        execute(
            "UPDATE phone_imeis SET status = 'in_stock' WHERE id IN (SELECT phone_imei_id FROM invoice_line_imeis WHERE invoice_line_id = :line_id)",
            ['line_id' => $line['line_id']]
        );

        // 4. Restaurer la quantité en stock
        if ($line['phone_id']) {
            execute(
                "UPDATE phones SET quantity = quantity + :qty, updated_at = CURRENT_TIMESTAMP WHERE id = :id",
                ['qty' => $line['quantity'], 'id' => $line['phone_id']]
            );

            // 5. Créer un mouvement de stock IN pour traçabilité
            execute(
                "INSERT INTO stock_movements (phone_id, user_id, type, quantity, reason) VALUES (:pid, :uid, 'IN', :qty, :reason)",
                [
                    'pid' => $line['phone_id'],
                    'uid' => $_SESSION['user_id'],
                    'qty' => $line['quantity'],
                    'reason' => "Annulation vente {$invoice['invoice_number']}"
                ]
            );
        }
    }

    $pdo->commit();
    $_SESSION['flash_message'] = "Facture {$invoice['invoice_number']} annulée. Le stock et les IMEI ont été restaurés.";
    $_SESSION['flash_type'] = 'success';
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['flash_message'] = "Erreur lors de l'annulation : " . $e->getMessage();
    $_SESSION['flash_type'] = 'danger';
}

header('Location: /pages/sales/list.php');
exit;
