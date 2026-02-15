<?php
$pageTitle = 'Historique des mouvements';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
requireLogin();

// Paramètres de filtrage et pagination
$phoneFilter = $_GET['phone'] ?? '';
$typeFilter = $_GET['type'] ?? '';
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

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Compter le total
$countSql = "SELECT COUNT(*) as total FROM stock_movements sm LEFT JOIN phones p ON sm.phone_id = p.id $whereClause";
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

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title">Historique des mouvements de stock</h1>
        <a href="/pages/stock/adjust.php" class="btn btn-primary">+ Nouveau mouvement</a>
    </div>

    <form method="GET" class="search-bar">
        <select name="phone" class="form-control">
            <option value="">Tous les téléphones</option>
            <?php foreach ($phones as $phone): ?>
                <option value="<?= $phone['id'] ?>" <?= $phoneFilter == $phone['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($phone['brand_name'] . ' - ' . $phone['model']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="type" class="form-control" style="max-width: 200px;">
            <option value="">Tous les types</option>
            <option value="IN" <?= $typeFilter === 'IN' ? 'selected' : '' ?>>Entrées</option>
            <option value="OUT" <?= $typeFilter === 'OUT' ? 'selected' : '' ?>>Sorties</option>
        </select>
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
                <a href="?page=<?= $page - 1 ?>&phone=<?= $phoneFilter ?>&type=<?= $typeFilter ?>">Précédent</a>
            <?php endif; ?>

            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="active"><?= $i ?></span>
                <?php else: ?>
                    <a href="?page=<?= $i ?>&phone=<?= $phoneFilter ?>&type=<?= $typeFilter ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>&phone=<?= $phoneFilter ?>&type=<?= $typeFilter ?>">Suivant</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <p class="text-muted text-center mt-2">
        <?= $total ?> mouvement(s) au total
    </p>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
