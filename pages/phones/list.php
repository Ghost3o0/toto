<?php
$pageTitle = 'Liste des téléphones';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireLogin();

// Paramètres de recherche et pagination
$search = $_GET['search'] ?? '';
$brandFilter = $_GET['brand'] ?? '';
$stockFilter = $_GET['stock'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Construction de la requête
$where = [];
$params = [];

if ($search) {
    $where[] = "(p.model ILIKE :search OR p.description ILIKE :search)";
    $params['search'] = "%$search%";
}

if ($brandFilter) {
    $where[] = "p.brand_id = :brand_id";
    $params['brand_id'] = $brandFilter;
}

if ($stockFilter === 'low') {
    $where[] = "p.quantity <= p.min_stock";
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Compter le total
$countSql = "SELECT COUNT(*) as total FROM phones p $whereClause";
$total = fetchOne($countSql, $params)['total'];
$totalPages = ceil($total / $perPage);

// Récupérer les téléphones
$sql = "SELECT p.*, b.name as brand_name
        FROM phones p
        LEFT JOIN brands b ON p.brand_id = b.id
        $whereClause
        ORDER BY p.updated_at DESC
        LIMIT $perPage OFFSET $offset";

$phones = fetchAll($sql, $params);

// Récupérer les marques pour le filtre
$brands = fetchAll("SELECT * FROM brands ORDER BY name");

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title">Téléphones en stock</h1>
        <a href="/pages/phones/add.php" class="btn btn-primary">+ Ajouter un téléphone</a>
    </div>

    <form method="GET" class="search-bar">
        <input type="text" name="search" class="form-control" placeholder="Rechercher un modèle..."
               value="<?= htmlspecialchars($search) ?>">
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
                            <td><?= number_format($phone['price'], 2, ',', ' ') ?> €</td>
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
