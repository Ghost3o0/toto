<?php
$pageTitle = 'Tableau de bord';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
requireLogin();

$uf = buildVisibleUserFilter();
$ufIn = $uf['placeholders'];
$ufParams = $uf['params'];

// Statistiques principales
$stats = [];

// Total produits en stock
$stats['total_quantity'] = fetchOne("SELECT COALESCE(SUM(quantity), 0) as total FROM phones WHERE user_id IN ($ufIn)", $ufParams)['total'];

// Nombre de références
$stats['total_references'] = fetchOne("SELECT COUNT(*) as total FROM phones WHERE user_id IN ($ufIn)", $ufParams)['total'];

// Valeur totale du stock
$stats['total_value'] = fetchOne("SELECT COALESCE(SUM(price * quantity), 0) as total FROM phones WHERE user_id IN ($ufIn)", $ufParams)['total'];

// Produits en stock bas
$stats['low_stock'] = fetchOne("SELECT COUNT(*) as total FROM phones WHERE quantity <= min_stock AND user_id IN ($ufIn)", $ufParams)['total'];

// Ventes du mois
$salesParams = array_merge($ufParams, []);
$stats['monthly_sales'] = fetchOne(
    "SELECT COALESCE(SUM(total_amount), 0) as total FROM invoices
     WHERE user_id IN ($ufIn) AND status = 'completed'
     AND created_at >= date_trunc('month', CURRENT_DATE)",
    $salesParams
)['total'];

$stats['monthly_invoices'] = fetchOne(
    "SELECT COUNT(*) as total FROM invoices
     WHERE user_id IN ($ufIn) AND status = 'completed'
     AND created_at >= date_trunc('month', CURRENT_DATE)",
    $salesParams
)['total'];

// Top 5 des téléphones les plus vendus (sorties)
$topSold = fetchAll(
    "SELECT p.model, b.name as brand_name, COALESCE(SUM(sm.quantity), 0) as total_sold
     FROM phones p
     LEFT JOIN brands b ON p.brand_id = b.id
     LEFT JOIN stock_movements sm ON p.id = sm.phone_id AND sm.type = 'OUT' AND sm.status != 'annule'
     WHERE p.user_id IN ($ufIn)
     GROUP BY p.id, p.model, b.name
     ORDER BY total_sold DESC
     LIMIT 5",
    $ufParams
);

// Mouvements récents
$recentMovements = fetchAll(
    "SELECT sm.*, p.model as phone_model, b.name as brand_name
     FROM stock_movements sm
     LEFT JOIN phones p ON sm.phone_id = p.id
     LEFT JOIN brands b ON p.brand_id = b.id
     WHERE p.user_id IN ($ufIn)
     ORDER BY sm.created_at DESC
     LIMIT 5",
    $ufParams
);

// Produits en alerte stock bas
$lowStockProducts = fetchAll(
    "SELECT p.*, b.name as brand_name
     FROM phones p
     LEFT JOIN brands b ON p.brand_id = b.id
     WHERE p.quantity <= p.min_stock AND p.user_id IN ($ufIn)
     ORDER BY p.quantity ASC
     LIMIT 5",
    $ufParams
);

// Rapport quotidien : ventes du jour
$todaySales = fetchAll(
    "SELECT i.invoice_number, i.client_name, i.total_amount, i.created_at,
            COUNT(il.id) as nb_articles, SUM(il.quantity) as nb_unites
     FROM invoices i
     LEFT JOIN invoice_lines il ON i.id = il.invoice_id
     WHERE i.user_id IN ($ufIn) AND i.status = 'completed'
     AND DATE(i.created_at) = CURRENT_DATE
     GROUP BY i.id, i.invoice_number, i.client_name, i.total_amount, i.created_at
     ORDER BY i.created_at DESC",
    $ufParams
);

$todayStats = fetchOne(
    "SELECT COUNT(*) as nb_ventes, COALESCE(SUM(total_amount), 0) as total_montant
     FROM invoices
     WHERE user_id IN ($ufIn) AND status = 'completed'
     AND DATE(created_at) = CURRENT_DATE",
    $ufParams
);

$todayUnits = fetchOne(
    "SELECT COALESCE(SUM(sm.quantity), 0) as total
     FROM stock_movements sm
     LEFT JOIN phones p ON sm.phone_id = p.id
     WHERE sm.type = 'OUT' AND sm.status != 'annule' AND p.user_id IN ($ufIn)
     AND DATE(sm.created_at) = CURRENT_DATE",
    $ufParams
);

// Statistiques d'hier (comparaison)
$yesterdayStats = fetchOne(
    "SELECT COUNT(*) as nb_ventes, COALESCE(SUM(total_amount), 0) as total_montant
     FROM invoices
     WHERE user_id IN ($ufIn) AND status = 'completed'
     AND DATE(created_at) = CURRENT_DATE - 1",
    $ufParams
);

$yesterdayUnits = fetchOne(
    "SELECT COALESCE(SUM(sm.quantity), 0) as total
     FROM stock_movements sm
     LEFT JOIN phones p ON sm.phone_id = p.id
     WHERE sm.type = 'OUT' AND sm.status != 'annule' AND p.user_id IN ($ufIn)
     AND DATE(sm.created_at) = CURRENT_DATE - 1",
    $ufParams
);

// Helper pour comparaison
function comparisonHtml($today, $yesterday) {
    $today = floatval($today);
    $yesterday = floatval($yesterday);
    if ($yesterday == 0 && $today == 0) {
        return '<div class="stat-comparison equal">= identique</div>';
    }
    if ($yesterday == 0) {
        return '<div class="stat-comparison up">&uarr; nouveau</div>';
    }
    $pct = round((($today - $yesterday) / $yesterday) * 100);
    if ($pct > 0) {
        return '<div class="stat-comparison up">&uarr; +' . $pct . '% vs hier</div>';
    } elseif ($pct < 0) {
        return '<div class="stat-comparison down">&darr; ' . $pct . '% vs hier</div>';
    }
    return '<div class="stat-comparison equal">= identique vs hier</div>';
}

require_once __DIR__ . '/../includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <h1>Tableau de bord</h1>
    <button class="btn-toggle-values" onclick="toggleHideValues()" title="Masquer/afficher les valeurs">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg> Masquer
    </button>
</div>

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
            <div class="stat-value" data-target="<?= $stats['total_quantity'] ?>"><?= number_format($stats['total_quantity'], 0, ',', ' ') ?></div>
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
            <div class="stat-value" data-target="<?= $stats['total_references'] ?>"><?= $stats['total_references'] ?></div>
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
            <div class="stat-value" data-monetary="true" data-target="<?= $stats['total_value'] ?>" data-suffix="Ar"><?= number_format($stats['total_value'], 0, ',', ' ') ?> Ar</div>
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
            <div class="stat-value" data-target="<?= $stats['low_stock'] ?>"><?= $stats['low_stock'] ?></div>
        </div>
    </div>
</div>

<!-- Statistiques ventes du mois -->
<div class="stats-grid" style="margin-bottom: 1.5rem;">
    <div class="stat-card">
        <div class="stat-icon green">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
            </svg>
        </div>
        <div class="stat-content">
            <h3>Ventes ce mois</h3>
            <div class="stat-value" data-monetary="true" data-target="<?= $stats['monthly_sales'] ?>" data-suffix="Ar"><?= number_format($stats['monthly_sales'], 0, ',', ' ') ?> Ar</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
            </svg>
        </div>
        <div class="stat-content">
            <h3>Factures ce mois</h3>
            <div class="stat-value" data-target="<?= $stats['monthly_invoices'] ?>"><?= $stats['monthly_invoices'] ?></div>
        </div>
    </div>
</div>

<!-- Rapport quotidien -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h2 class="card-title">Rapport du jour (<?= date('d/m/Y') ?>)</h2>
    </div>
    <div class="stats-grid" style="margin-bottom: 1rem;">
        <div class="stat-card">
            <div class="stat-icon green">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                </svg>
            </div>
            <div class="stat-content">
                <h3>Ventes aujourd'hui</h3>
                <div class="stat-value" data-target="<?= $todayStats['nb_ventes'] ?>"><?= $todayStats['nb_ventes'] ?></div>
                <?= comparisonHtml($todayStats['nb_ventes'], $yesterdayStats['nb_ventes']) ?>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
            <div class="stat-content">
                <h3>Unités sorties</h3>
                <div class="stat-value" data-target="<?= $todayUnits['total'] ?>"><?= $todayUnits['total'] ?></div>
                <?= comparisonHtml($todayUnits['total'], $yesterdayUnits['total']) ?>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="stat-content">
                <h3>Chiffre du jour</h3>
                <div class="stat-value" data-monetary="true" data-target="<?= $todayStats['total_montant'] ?>" data-suffix="Ar"><?= number_format($todayStats['total_montant'], 0, ',', ' ') ?> Ar</div>
                <?= comparisonHtml($todayStats['total_montant'], $yesterdayStats['total_montant']) ?>
            </div>
        </div>
    </div>
    <?php if (!empty($todaySales)): ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Heure</th>
                        <th>Facture</th>
                        <th>Client</th>
                        <th>Articles</th>
                        <th>Montant</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($todaySales as $sale): ?>
                        <tr>
                            <td><?= date('H:i', strtotime($sale['created_at'])) ?></td>
                            <td><strong><?= htmlspecialchars($sale['invoice_number']) ?></strong></td>
                            <td><?= htmlspecialchars($sale['client_name']) ?></td>
                            <td><?= $sale['nb_unites'] ?> unité(s)</td>
                            <td><strong><?= number_format($sale['total_amount'], 0, ',', ' ') ?> Ar</strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-muted text-center">Aucune vente aujourd'hui</p>
    <?php endif; ?>
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
// Counter animations
initCounterAnimations();

// Apply hide values if previously set
if (localStorage.getItem('hide_values') === 'true') {
    applyHideValues(true);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
