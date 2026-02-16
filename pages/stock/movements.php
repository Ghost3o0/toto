<?php
$pageTitle = 'Historique des mouvements';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
requireLogin();

// Paramètres de filtrage et pagination
$phoneFilter = $_GET['phone'] ?? '';
$typeFilter = $_GET['type'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Filtrage par utilisateurs visibles
$uf = buildVisibleUserFilter();
$where = ["p.user_id IN ({$uf['placeholders']})"];
$params = $uf['params'];

if ($phoneFilter) {
    $where[] = "sm.phone_id = :phone_id";
    $params['phone_id'] = $phoneFilter;
}

if ($typeFilter) {
    $where[] = "sm.type = :type";
    $params['type'] = $typeFilter;
}

if ($dateFrom) {
    $where[] = "DATE(sm.created_at) >= :date_from";
    $params['date_from'] = $dateFrom;
}

if ($dateTo) {
    $where[] = "DATE(sm.created_at) <= :date_to";
    $params['date_to'] = $dateTo;
}

if ($search) {
    $where[] = "(p.model ILIKE :search OR b.name ILIKE :search OR sm.reason ILIKE :search)";
    $params['search'] = "%$search%";
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $allMovements = fetchAll(
        "SELECT sm.*, p.model as phone_model, b.name as brand_name, u.username
         FROM stock_movements sm
         LEFT JOIN phones p ON sm.phone_id = p.id
         LEFT JOIN brands b ON p.brand_id = b.id
         LEFT JOIN users u ON sm.user_id = u.id
         $whereClause
         ORDER BY sm.created_at DESC",
        $params
    );

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=mouvements_' . date('Y-m-d') . '.csv');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM
    fputcsv($out, ['Date', 'Téléphone', 'Type', 'Quantité', 'Raison', 'Utilisateur'], ';');
    foreach ($allMovements as $mv) {
        fputcsv($out, [
            date('d/m/Y H:i', strtotime($mv['created_at'])),
            ($mv['brand_name'] ?? '') . ' ' . $mv['phone_model'],
            $mv['type'] === 'IN' ? 'Entrée' : 'Sortie',
            ($mv['type'] === 'IN' ? '+' : '-') . $mv['quantity'],
            $mv['reason'] ?? '',
            $mv['username'] ?? 'Système'
        ], ';');
    }
    fclose($out);
    exit;
}

// Compter le total
$countSql = "SELECT COUNT(*) as total FROM stock_movements sm
             LEFT JOIN phones p ON sm.phone_id = p.id
             LEFT JOIN brands b ON p.brand_id = b.id
             $whereClause";
$total = fetchOne($countSql, $params)['total'];
$totalPages = ceil($total / $perPage);

// Récupérer les mouvements
$sql = "SELECT sm.*, p.model as phone_model, b.name as brand_name, u.username
        FROM stock_movements sm
        LEFT JOIN phones p ON sm.phone_id = p.id
        LEFT JOIN brands b ON p.brand_id = b.id
        LEFT JOIN users u ON sm.user_id = u.id
        $whereClause
        ORDER BY sm.created_at DESC
        LIMIT $perPage OFFSET $offset";

$movements = fetchAll($sql, $params);

// Récupérer les téléphones visibles pour le filtre
$phones = fetchAll(
    "SELECT p.id, p.model, b.name as brand_name
     FROM phones p
     LEFT JOIN brands b ON p.brand_id = b.id
     WHERE p.user_id IN ({$uf['placeholders']})
     ORDER BY b.name, p.model",
    $uf['params']
);

// AJAX support
$isAjax = isset($_GET['ajax']) && $_GET['ajax'] === '1';
if (!$isAjax) {
    require_once __DIR__ . '/../../includes/header.php';
}
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title">Historique des mouvements de stock</h1>
        <div class="btn-group">
            <a href="/pages/stock/adjust.php" class="btn btn-primary">+ Nouveau mouvement</a>
            <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="btn-export" title="Exporter CSV">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/>
                </svg>
                CSV
            </a>
            <button class="btn-compact-toggle" onclick="toggleCompactMode()">Compact</button>
        </div>
    </div>

    <form method="GET" class="search-bar">
        <input type="text" name="search" class="form-control" placeholder="Rechercher modèle, raison..."
               value="<?= htmlspecialchars($search) ?>">
        <select name="phone" class="form-control">
            <option value="">Tous les téléphones</option>
            <?php foreach ($phones as $phone): ?>
                <option value="<?= $phone['id'] ?>" <?= $phoneFilter == $phone['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($phone['brand_name'] . ' - ' . $phone['model']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="type" class="form-control" style="max-width: 160px;">
            <option value="">Tous les types</option>
            <option value="IN" <?= $typeFilter === 'IN' ? 'selected' : '' ?>>Entrées</option>
            <option value="OUT" <?= $typeFilter === 'OUT' ? 'selected' : '' ?>>Sorties</option>
        </select>
        <input type="date" name="date_from" class="form-control" style="max-width: 170px;"
               value="<?= htmlspecialchars($dateFrom) ?>" title="Date début">
        <input type="date" name="date_to" class="form-control" style="max-width: 170px;"
               value="<?= htmlspecialchars($dateTo) ?>" title="Date fin">
        <button type="submit" class="btn btn-primary">Filtrer</button>
        <a href="/pages/stock/movements.php" class="btn btn-outline">Réinitialiser</a>
    </form>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Téléphone</th>
                    <th>Type</th>
                    <th>Quantité</th>
                    <th>Raison</th>
                    <th>Utilisateur</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($movements)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">Aucun mouvement trouvé</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($movements as $movement): ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', strtotime($movement['created_at'])) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($movement['brand_name'] ?? '') ?></strong>
                                <?= htmlspecialchars($movement['phone_model']) ?>
                            </td>
                            <td>
                                <?php if ($movement['type'] === 'IN'): ?>
                                    <span class="badge badge-success">Entrée</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Sortie</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong class="<?= $movement['type'] === 'IN' ? 'stock-ok' : 'stock-low' ?>">
                                    <?= $movement['type'] === 'IN' ? '+' : '-' ?><?= $movement['quantity'] ?>
                                </strong>
                            </td>
                            <td><?= htmlspecialchars($movement['reason'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($movement['username'] ?? 'Système') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>&phone=<?= $phoneFilter ?>&type=<?= $typeFilter ?>&date_from=<?= $dateFrom ?>&date_to=<?= $dateTo ?>&search=<?= urlencode($search) ?>">Précédent</a>
            <?php endif; ?>

            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="active"><?= $i ?></span>
                <?php else: ?>
                    <a href="?page=<?= $i ?>&phone=<?= $phoneFilter ?>&type=<?= $typeFilter ?>&date_from=<?= $dateFrom ?>&date_to=<?= $dateTo ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>&phone=<?= $phoneFilter ?>&type=<?= $typeFilter ?>&date_from=<?= $dateFrom ?>&date_to=<?= $dateTo ?>&search=<?= urlencode($search) ?>">Suivant</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <p class="text-muted text-center mt-2">
        <?= $total ?> mouvement(s) au total
    </p>
</div>

<?php if (!$isAjax): ?>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<?php endif; ?>
