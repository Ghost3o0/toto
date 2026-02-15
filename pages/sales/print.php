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
    "SELECT i.*, u.username FROM invoices i LEFT JOIN users u ON i.user_id = u.id
     WHERE i.id = :id AND i.user_id IN ({$uf['placeholders']})",
    $params
);

if (!$invoice) {
    header('Location: /pages/sales/list.php');
    exit;
}

$lines = fetchAll(
    "SELECT * FROM invoice_lines WHERE invoice_id = :id ORDER BY id",
    ['id' => $id]
);

// Récupérer les IMEI associés aux lignes de facture
$imeiRows = fetchAll(
    "SELECT il.invoice_line_id, pi.imei FROM invoice_line_imeis il
     JOIN phone_imeis pi ON il.phone_imei_id = pi.id
     JOIN invoice_lines l ON l.id = il.invoice_line_id
     WHERE l.invoice_id = :id",
    ['id' => $id]
);
$lineImeis = [];
foreach ($imeiRows as $r) {
    $lineImeis[$r['invoice_line_id']][] = $r['imei'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture <?= htmlspecialchars($invoice['invoice_number']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #1e293b; font-size: 14px; padding: 2rem; max-width: 800px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 3px solid #2563eb; }
        .header h1 { font-size: 1.8rem; color: #2563eb; }
        .header .company { font-size: 0.9rem; color: #64748b; }
        .invoice-info { display: flex; justify-content: space-between; margin-bottom: 2rem; }
        .invoice-info .block { flex: 1; }
        .invoice-info .block h3 { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; margin-bottom: 0.5rem; }
        .invoice-info .block p { margin-bottom: 0.25rem; }
        .status-badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; }
        .status-completed { background: #dcfce7; color: #166534; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 1.5rem; }
        th { background: #f8fafc; color: #64748b; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em; padding: 0.75rem; text-align: left; border-bottom: 2px solid #e2e8f0; }
        td { padding: 0.75rem; border-bottom: 1px solid #e2e8f0; }
        tfoot td { border-top: 2px solid #1e293b; border-bottom: none; font-weight: 700; font-size: 1.1rem; }
        .text-right { text-align: right; }
        .notes { padding: 1rem; background: #f8fafc; border-radius: 8px; margin-bottom: 1.5rem; }
        .print-btn { display: inline-block; padding: 0.75rem 1.5rem; background: #2563eb; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 0.9rem; }
        .print-btn:hover { background: #1d4ed8; }
        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
            .header { border-bottom-color: #000; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: right; margin-bottom: 1rem;">
        <button class="print-btn" onclick="window.print()">Imprimer</button>
    </div>

    <div class="header">
        <div>
            <h1>Mystate</h1>
            <p class="company">Gestion de Stock de Téléphones</p>
        </div>
        <div style="text-align: right;">
            <h2 style="font-size: 1.2rem;">FACTURE</h2>
            <p><strong><?= htmlspecialchars($invoice['invoice_number']) ?></strong></p>
            <p><?= date('d/m/Y', strtotime($invoice['created_at'])) ?></p>
            <span class="status-badge <?= $invoice['status'] === 'completed' ? 'status-completed' : 'status-cancelled' ?>">
                <?= $invoice['status'] === 'completed' ? 'Terminée' : 'Annulée' ?>
            </span>
        </div>
    </div>

    <div class="invoice-info">
        <div class="block">
            <h3>Vendeur</h3>
            <p><strong><?= htmlspecialchars($invoice['username']) ?></strong></p>
        </div>
        <div class="block">
            <h3>Client</h3>
            <p><strong><?= htmlspecialchars($invoice['client_name']) ?></strong></p>
            <?php if ($invoice['client_phone']): ?>
                <p>Tél: <?= htmlspecialchars($invoice['client_phone']) ?></p>
            <?php endif; ?>
            <?php if ($invoice['client_address']): ?>
                <p><?= htmlspecialchars($invoice['client_address']) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Désignation</th>
                <th>Qté</th>
                <th>Prix unitaire</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lines as $i => $line): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars(($line['phone_brand'] ? $line['phone_brand'] . ' ' : '') . $line['phone_model']) ?></td>
                    <td><?= $line['quantity'] ?></td>
                    <td><?= number_format($line['unit_price'], 0, ',', ' ') ?> Ar</td>
                    <td class="text-right"><?= number_format($line['line_total'], 0, ',', ' ') ?> Ar</td>
                </tr>
                <?php if (!empty($lineImeis[$line['id']])): ?>
                    <tr>
                        <td></td>
                        <td colspan="4"><small class="text-muted">IMEI(s): <?= htmlspecialchars(implode(', ', $lineImeis[$line['id']])) ?></small></td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-right">TOTAL</td>
                <td class="text-right"><?= number_format($invoice['total_amount'], 0, ',', ' ') ?> Ar</td>
            </tr>
        </tfoot>
    </table>

    <?php if ($invoice['notes']): ?>
        <div class="notes">
            <strong>Notes :</strong> <?= htmlspecialchars($invoice['notes']) ?>
        </div>
    <?php endif; ?>

    <p style="text-align: center; color: #64748b; font-size: 0.8rem; margin-top: 3rem;">
        Merci pour votre achat !
    </p>
</body>
</html>
