<?php
$pageTitle = 'Historique des ventes';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
requireLogin();

$uf = buildVisibleUserFilter();

// Paramètres de recherche et pagination
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$dateFilter = $_GET['date'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$where = ["i.user_id IN ({$uf['placeholders']})"];
$params = $uf['params'];

if ($search) {
    $where[] = "(i.invoice_number ILIKE :search OR i.client_name ILIKE :search)";
    $params['search'] = "%$search%";
}

if ($statusFilter) {
    $where[] = "i.status = :status";
    $params['status'] = $statusFilter;
}

if ($dateFilter) {
    $where[] = "DATE(i.created_at) = :date";
    $params['date'] = $dateFilter;
}

$whereClause = 'WHERE ' . implode(' AND ', $where);

$total = fetchOne("SELECT COUNT(*) as total FROM invoices i $whereClause", $params)['total'];
$totalPages = ceil($total / $perPage);

$invoices = fetchAll(
    "SELECT i.*, u.username,
            (SELECT COUNT(*) FROM invoice_lines il WHERE il.invoice_id = i.id) as line_count
     FROM invoices i
     LEFT JOIN users u ON i.user_id = u.id
     $whereClause
     ORDER BY i.created_at DESC
     LIMIT $perPage OFFSET $offset",
    $params
);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title">Historique des ventes</h1>
        <a href="/pages/sales/create.php" class="btn btn-primary">+ Nouvelle vente</a>
    </div>

    <form method="GET" class="search-bar">
        <input type="text" name="search" class="form-control" placeholder="N° facture, client..."
               value="<?= htmlspecialchars($search) ?>">
        <input type="date" name="date" class="form-control" style="max-width: 200px;"
               value="<?= htmlspecialchars($dateFilter) ?>">
        <select name="status" class="form-control" style="max-width: 200px;">
            <option value="">Tous les statuts</option>
            <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Terminée</option>
            <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Annulée</option>
        </select>
        <button type="submit" class="btn btn-primary">Filtrer</button>
        <a href="/pages/sales/list.php" class="btn btn-outline">Réinitialiser</a>
    </form>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>N° Facture</th>
                    <th>Client</th>
                    <th>Articles</th>
                    <th>Total</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($invoices)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted">Aucune vente trouvée</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($invoices as $invoice): ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', strtotime($invoice['created_at'])) ?></td>
                            <td><strong><?= htmlspecialchars($invoice['invoice_number']) ?></strong></td>
                            <td><?= htmlspecialchars($invoice['client_name']) ?></td>
                            <td><?= $invoice['line_count'] ?></td>
                            <td><strong><?= number_format($invoice['total_amount'], 0, ',', ' ') ?> Ar</strong></td>
                            <td>
                                <?php if ($invoice['status'] === 'completed'): ?>
                                    <span class="badge badge-success">Terminée</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Annulée</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="/pages/sales/view.php?id=<?= $invoice['id'] ?>" class="btn btn-sm btn-outline">Voir</a>
                                    <a href="/pages/sales/print.php?id=<?= $invoice['id'] ?>" class="btn btn-sm btn-primary" target="_blank">Imprimer</a>
                                    <?php if ($invoice['status'] === 'completed'): ?>
                                        <a href="/pages/sales/cancel.php?id=<?= $invoice['id'] ?>"
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Annuler cette facture ?')">Annuler</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= $statusFilter ?>&date=<?= $dateFilter ?>">Précédent</a>
            <?php endif; ?>
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="active"><?= $i ?></span>
                <?php else: ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= $statusFilter ?>&date=<?= $dateFilter ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= $statusFilter ?>&date=<?= $dateFilter ?>">Suivant</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <p class="text-muted text-center mt-2">
        <?= $total ?> vente(s) au total
    </p>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
