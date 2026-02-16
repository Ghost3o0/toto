<?php
$pageTitle = 'Liste des téléphones';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
requireLogin();

// Paramètres de recherche et pagination
$search = $_GET['search'] ?? '';
$brandFilter = $_GET['brand'] ?? '';
$stockFilter = $_GET['stock'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Filtrage par utilisateurs visibles
$uf = buildVisibleUserFilter();
$where = ["p.user_id IN ({$uf['placeholders']})"];
$params = $uf['params'];

// Vérifier si la table phone_imeis existe avant de l'utiliser
$hasImeis = tableExists('phone_imeis');

if ($search) {
    $searchClause = "(p.model ILIKE :search OR p.description ILIKE :search OR p.barcode ILIKE :search";
    if ($hasImeis) {
        $searchClause .= " OR pi.imei ILIKE :search";
    }
    $searchClause .= ")";
    $where[] = $searchClause;
    $params['search'] = "%$search%";
}

if ($brandFilter) {
    $where[] = "p.brand_id = :brand_id";
    $params['brand_id'] = $brandFilter;
}

if ($stockFilter === 'low') {
    $where[] = "p.quantity <= p.min_stock";
}

$whereClause = 'WHERE ' . implode(' AND ', $where);

// Compter le total
$join = $hasImeis ? 'LEFT JOIN phone_imeis pi ON p.id = pi.phone_id' : '';
$countSql = "SELECT COUNT(DISTINCT p.id) as total FROM phones p " . ($join ? $join : '') . " $whereClause";
$total = fetchOne($countSql, $params)['total'];
$totalPages = ceil($total / $perPage);

// Récupérer les téléphones
$sql = "SELECT DISTINCT p.*, b.name as brand_name
    FROM phones p
    LEFT JOIN brands b ON p.brand_id = b.id
    " . ($join ? $join : '') . "
    $whereClause
    ORDER BY p.updated_at DESC
    LIMIT $perPage OFFSET $offset";

$phones = fetchAll($sql, $params);

// Récupérer les marques pour le filtre
$brands = fetchAll("SELECT * FROM brands ORDER BY name");

// Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // Re-fetch all without pagination
    $allPhones = fetchAll(
        "SELECT DISTINCT p.*, b.name as brand_name
         FROM phones p
         LEFT JOIN brands b ON p.brand_id = b.id
         " . ($join ? $join : '') . "
         $whereClause
         ORDER BY p.updated_at DESC",
        $params
    );

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=telephones_' . date('Y-m-d') . '.csv');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM
    fputcsv($out, ['Marque', 'Modèle', 'Prix', 'Quantité', 'Stock min', 'Statut'], ';');
    foreach ($allPhones as $ph) {
        $status = 'OK';
        if ($ph['quantity'] <= $ph['min_stock']) $status = 'Stock bas';
        elseif ($ph['quantity'] <= $ph['min_stock'] * 2) $status = 'Attention';
        fputcsv($out, [
            $ph['brand_name'] ?? 'N/A',
            $ph['model'],
            number_format($ph['price'], 2, ',', ' ') . ' Ar',
            $ph['quantity'],
            $ph['min_stock'],
            $status
        ], ';');
    }
    fclose($out);
    exit;
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title">Téléphones en stock</h1>
        <div class="btn-group no-collapse">
            <a href="/pages/phones/add.php" class="btn btn-primary">+ Ajouter un téléphone</a>
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
        <div style="display: flex; gap: 0.5rem; flex: 1; min-width: 200px;">
            <input type="text" name="search" id="search-input" class="form-control" placeholder="Rechercher modèle, code-barres..."
                   value="<?= htmlspecialchars($search) ?>">
            <button type="button" onclick="openBarcodeScanner(code => { document.getElementById('search-input').value = code; document.getElementById('search-input').form.submit(); })" class="btn btn-outline" title="Scanner">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M3 7V5a2 2 0 012-2h2M17 3h2a2 2 0 012 2v2M21 17v2a2 2 0 01-2 2h-2M7 21H5a2 2 0 01-2-2v-2M7 8h10M7 12h10M7 16h10"/>
                </svg>
            </button>
        </div>
        <select name="brand" class="form-control" style="max-width: 200px;">
            <option value="">Toutes les marques</option>
            <?php foreach ($brands as $brand): ?>
                <option value="<?= $brand['id'] ?>" <?= $brandFilter == $brand['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($brand['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="stock" class="form-control" style="max-width: 200px;">
            <option value="">Tout le stock</option>
            <option value="low" <?= $stockFilter === 'low' ? 'selected' : '' ?>>Stock bas</option>
        </select>
        <button type="submit" class="btn btn-primary">Filtrer</button>
        <a href="/pages/phones/list.php" class="btn btn-outline">Réinitialiser</a>
    </form>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Marque</th>
                    <th>Modèle</th>
                    <th>Prix</th>
                    <th>Quantité</th>
                    <th>Stock min</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($phones)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted">Aucun téléphone trouvé</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($phones as $phone): ?>
                        <tr>
                            <td><?= htmlspecialchars($phone['brand_name'] ?? 'N/A') ?></td>
                            <td>
                                <strong><?= htmlspecialchars($phone['model']) ?></strong>
                                <?php if ($phone['description']): ?>
                                    <br><small class="text-muted"><?= htmlspecialchars(substr($phone['description'], 0, 50)) ?>...</small>
                                <?php endif; ?>
                            </td>
                            <td><?= number_format($phone['price'], 2, ',', ' ') ?> Ar</td>
                            <td class="<?= $phone['quantity'] <= $phone['min_stock'] ? 'stock-low' : 'stock-ok' ?>">
                                <?= $phone['quantity'] ?>
                            </td>
                            <td><?= $phone['min_stock'] ?></td>
                            <td>
                                <?php if ($phone['quantity'] <= $phone['min_stock']): ?>
                                    <span class="badge badge-danger">Stock bas</span>
                                <?php elseif ($phone['quantity'] <= $phone['min_stock'] * 2): ?>
                                    <span class="badge badge-warning">Attention</span>
                                <?php else: ?>
                                    <span class="badge badge-success">OK</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="/pages/stock/adjust.php?id=<?= $phone['id'] ?>" class="btn btn-sm btn-success">Stock</a>
                                    <a href="/pages/phones/edit.php?id=<?= $phone['id'] ?>" class="btn btn-sm btn-outline">Modifier</a>
                                    <a href="/pages/phones/delete.php?id=<?= $phone['id'] ?>" class="btn btn-sm btn-danger"
                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce téléphone ?')">Supprimer</a>
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
                <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&brand=<?= $brandFilter ?>&stock=<?= $stockFilter ?>">Précédent</a>
            <?php endif; ?>

            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="active"><?= $i ?></span>
                <?php else: ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&brand=<?= $brandFilter ?>&stock=<?= $stockFilter ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&brand=<?= $brandFilter ?>&stock=<?= $stockFilter ?>">Suivant</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <p class="text-muted text-center mt-2">
        <?= $total ?> téléphone(s) au total
    </p>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
