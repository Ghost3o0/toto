<?php
$pageTitle = 'Détail facture';
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
    $_SESSION['flash_message'] = 'Facture introuvable.';
    $_SESSION['flash_type'] = 'danger';
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

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title">Facture <?= htmlspecialchars($invoice['invoice_number']) ?></h1>
        <div class="btn-group">
            <a href="/pages/sales/print.php?id=<?= $invoice['id'] ?>" class="btn btn-primary" target="_blank">Imprimer</a>
            <?php if ($invoice['status'] === 'completed'): ?>
                <a href="/pages/sales/cancel.php?id=<?= $invoice['id'] ?>"
                   class="btn btn-danger"
                   onclick="return confirm('Annuler cette facture ?')">Annuler</a>
            <?php endif; ?>
            <a href="/pages/sales/list.php" class="btn btn-outline">Retour</a>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
        <div>
            <h3 style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.5rem;">Informations facture</h3>
            <p><strong>N° :</strong> <?= htmlspecialchars($invoice['invoice_number']) ?></p>
            <p><strong>Date :</strong> <?= date('d/m/Y H:i', strtotime($invoice['created_at'])) ?></p>
            <p><strong>Statut :</strong>
                <?php if ($invoice['status'] === 'completed'): ?>
                    <span class="badge badge-success">Terminée</span>
                <?php else: ?>
                    <span class="badge badge-danger">Annulée</span>
                <?php endif; ?>
            </p>
            <p><strong>Vendeur :</strong> <?= htmlspecialchars($invoice['username']) ?></p>
        </div>
        <div>
            <h3 style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.5rem;">Client</h3>
            <p><strong>Nom :</strong> <?= htmlspecialchars($invoice['client_name']) ?></p>
            <?php if ($invoice['client_phone']): ?>
                <p><strong>Tél :</strong> <?= htmlspecialchars($invoice['client_phone']) ?></p>
            <?php endif; ?>
            <?php if ($invoice['client_address']): ?>
                <p><strong>Adresse :</strong> <?= htmlspecialchars($invoice['client_address']) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Produit</th>
                    <th>Quantité</th>
                    <th>Prix unitaire</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lines as $i => $line): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td>
                            <strong><?= htmlspecialchars($line['phone_brand'] ?? '') ?></strong>
                            <?= htmlspecialchars($line['phone_model']) ?>
                            <?php if (!empty($lineImeis[$line['id']])): ?>
                                <br><small class="text-muted">IMEI(s): <?= htmlspecialchars(implode(', ', $lineImeis[$line['id']])) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= $line['quantity'] ?></td>
                        <td><?= number_format($line['unit_price'], 0, ',', ' ') ?> Ar</td>
                        <td><strong><?= number_format($line['line_total'], 0, ',', ' ') ?> Ar</strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="text-right"><strong>Total :</strong></td>
                    <td><strong style="font-size: 1.1rem;"><?= number_format($invoice['total_amount'], 0, ',', ' ') ?> Ar</strong></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <?php if ($invoice['notes']): ?>
        <div style="margin-top: 1rem; padding: 1rem; background: var(--bg-color); border-radius: var(--radius);">
            <strong>Notes :</strong> <?= htmlspecialchars($invoice['notes']) ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
