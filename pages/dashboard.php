<?php
$pageTitle = 'Tableau de bord';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireLogin();

// Statistiques principales
$stats = [];

// Total produits en stock
$stats['total_quantity'] = fetchOne("SELECT COALESCE(SUM(quantity), 0) as total FROM phones")['total'];

// Nombre de références
$stats['total_references'] = fetchOne("SELECT COUNT(*) as total FROM phones")['total'];

// Valeur totale du stock
$stats['total_value'] = fetchOne("SELECT COALESCE(SUM(price * quantity), 0) as total FROM phones")['total'];

// Produits en stock bas
$stats['low_stock'] = fetchOne("SELECT COUNT(*) as total FROM phones WHERE quantity <= min_stock")['total'];

// Répartition par marque (pour le graphique)
$brandDistribution = fetchAll(
    "SELECT b.name, COALESCE(SUM(p.quantity), 0) as total
     FROM brands b
     LEFT JOIN phones p ON b.id = p.brand_id
     GROUP BY b.id, b.name
     HAVING COALESCE(SUM(p.quantity), 0) > 0
     ORDER BY total DESC
     LIMIT 10"
);

// Top 5 des téléphones les plus vendus (sorties)
$topSold = fetchAll(
    "SELECT p.model, b.name as brand_name, COALESCE(SUM(sm.quantity), 0) as total_sold
     FROM phones p
     LEFT JOIN brands b ON p.brand_id = b.id
     LEFT JOIN stock_movements sm ON p.id = sm.phone_id AND sm.type = 'OUT'
     GROUP BY p.id, p.model, b.name
     ORDER BY total_sold DESC
     LIMIT 5"
);

// Mouvements récents
$recentMovements = fetchAll(
    "SELECT sm.*, p.model as phone_model, b.name as brand_name
     FROM stock_movements sm
     LEFT JOIN phones p ON sm.phone_id = p.id
     LEFT JOIN brands b ON p.brand_id = b.id
     ORDER BY sm.created_at DESC
     LIMIT 5"
);

// Produits en alerte stock bas
$lowStockProducts = fetchAll(
    "SELECT p.*, b.name as brand_name
     FROM phones p
     LEFT JOIN brands b ON p.brand_id = b.id
     WHERE p.quantity <= p.min_stock
     ORDER BY p.quantity ASC
     LIMIT 5"
);

// Mouvements des 7 derniers jours (pour le graphique)
$weekMovements = fetchAll(
    "SELECT DATE(created_at) as date,
            SUM(CASE WHEN type = 'IN' THEN quantity ELSE 0 END) as entries,
            SUM(CASE WHEN type = 'OUT' THEN quantity ELSE 0 END) as exits
     FROM stock_movements
     WHERE created_at >= CURRENT_DATE - INTERVAL '7 days'
     GROUP BY DATE(created_at)
     ORDER BY date"
);

require_once __DIR__ . '/../includes/header.php';
?>

<h1 style="margin-bottom: 1.5rem;">Tableau de bord</h1>

<!-- Statistiques principales -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
        </div>
        <div class="stat-content">
            <h3>Total en stock</h3>
            <div class="stat-value"><?= number_format($stats['total_quantity'], 0, ',', ' ') ?></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon green">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
        </div>
        <div class="stat-content">
            <h3>Références</h3>
            <div class="stat-value"><?= $stats['total_references'] ?></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon orange">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div class="stat-content">
            <h3>Valeur du stock</h3>
            <div class="stat-value"><?= number_format($stats['total_value'], 0, ',', ' ') ?> €</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon red">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <div class="stat-content">
            <h3>Stock bas</h3>
            <div class="stat-value"><?= $stats['low_stock'] ?></div>
        </div>
    </div>
</div>

<!-- Graphiques -->
<div class="charts-grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Répartition par marque</h2>
        </div>
        <div class="chart-container">
            <canvas id="brandChart"></canvas>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Mouvements (7 derniers jours)</h2>
        </div>
        <div class="chart-container">
            <canvas id="movementsChart"></canvas>
        </div>
    </div>
</div>

<!-- Alertes et informations -->
<div class="charts-grid">
    <!-- Stock bas -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Alertes stock bas</h2>
            <a href="/pages/phones/list.php?stock=low" class="btn btn-sm btn-outline">Voir tout</a>
        </div>
        <?php if (empty($lowStockProducts)): ?>
            <p class="text-muted text-center">Aucune alerte de stock</p>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th>Stock</th>
                            <th>Min</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lowStockProducts as $product): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($product['brand_name'] ?? '') ?></strong>
                                    <?= htmlspecialchars($product['model']) ?>
                                </td>
                                <td class="stock-low"><?= $product['quantity'] ?></td>
                                <td><?= $product['min_stock'] ?></td>
                                <td>
                                    <a href="/pages/stock/adjust.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-success">
                                        Réappro
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Mouvements récents -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Derniers mouvements</h2>
            <a href="/pages/stock/movements.php" class="btn btn-sm btn-outline">Voir tout</a>
        </div>
        <?php if (empty($recentMovements)): ?>
            <p class="text-muted text-center">Aucun mouvement récent</p>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Produit</th>
                            <th>Mouvement</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentMovements as $movement): ?>
                            <tr>
                                <td><?= date('d/m H:i', strtotime($movement['created_at'])) ?></td>
                                <td><?= htmlspecialchars($movement['phone_model']) ?></td>
                                <td>
                                    <?php if ($movement['type'] === 'IN'): ?>
                                        <span class="badge badge-success">+<?= $movement['quantity'] ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">-<?= $movement['quantity'] ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Top ventes -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Top 5 des ventes</h2>
    </div>
    <?php if (empty($topSold) || $topSold[0]['total_sold'] == 0): ?>
        <p class="text-muted text-center">Aucune vente enregistrée</p>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Produit</th>
                        <th>Unités vendues</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topSold as $index => $product): ?>
                        <?php if ($product['total_sold'] > 0): ?>
                            <tr>
                                <td><strong><?= $index + 1 ?></strong></td>
                                <td>
                                    <?= htmlspecialchars($product['brand_name'] ?? '') ?>
                                    <?= htmlspecialchars($product['model']) ?>
                                </td>
                                <td><strong><?= $product['total_sold'] ?></strong></td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
// Données pour les graphiques
const brandData = <?= json_encode($brandDistribution) ?>;
const movementsData = <?= json_encode($weekMovements) ?>;

// Graphique répartition par marque
if (brandData.length > 0) {
    new Chart(document.getElementById('brandChart'), {
        type: 'doughnut',
        data: {
            labels: brandData.map(b => b.name),
            datasets: [{
                data: brandData.map(b => b.total),
                backgroundColor: [
                    '#3b82f6', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6',
                    '#06b6d4', '#ec4899', '#14b8a6', '#f97316', '#6366f1'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    });
}

// Graphique mouvements
if (movementsData.length > 0) {
    new Chart(document.getElementById('movementsChart'), {
        type: 'bar',
        data: {
            labels: movementsData.map(m => {
                const d = new Date(m.date);
                return d.toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric' });
            }),
            datasets: [{
                label: 'Entrées',
                data: movementsData.map(m => m.entries),
                backgroundColor: '#22c55e'
            }, {
                label: 'Sorties',
                data: movementsData.map(m => m.exits),
                backgroundColor: '#ef4444'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
} else {
    document.getElementById('movementsChart').parentElement.innerHTML =
        '<p class="text-muted text-center" style="padding: 2rem;">Aucun mouvement cette semaine</p>';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
