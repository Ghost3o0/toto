<?php
$pageTitle = 'Recherche IMEI';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
requireLogin();

$uf = buildVisibleUserFilter();
$searchImei = trim($_GET['imei'] ?? '');
$results = [];

if ($searchImei) {
    $results = fetchAll(
        "SELECT pi.imei, p.model, b.name as brand_name, p.price, p.quantity,
                CASE WHEN ili.id IS NOT NULL THEN 'vendu' ELSE 'en_stock' END as statut,
                i.invoice_number, i.client_name, i.created_at as sale_date
         FROM phone_imeis pi
         JOIN phones p ON pi.phone_id = p.id
         LEFT JOIN brands b ON p.brand_id = b.id
         LEFT JOIN invoice_line_imeis ili ON ili.phone_imei_id = pi.id
         LEFT JOIN invoice_lines il ON il.id = ili.invoice_line_id
         LEFT JOIN invoices i ON i.id = il.invoice_id AND i.status = 'completed'
         WHERE pi.imei ILIKE :search AND p.user_id IN ({$uf['placeholders']})",
        array_merge(['search' => "%$searchImei%"], $uf['params'])
    );
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title">Recherche par IMEI</h1>
    </div>

    <div class="imei-search-box">
        <form method="GET" class="search-input-group">
            <input type="text" name="imei" class="form-control" placeholder="Entrez un numéro IMEI..."
                   value="<?= htmlspecialchars($searchImei) ?>" autofocus>
            <button type="button" onclick="openBarcodeScanner(code => { document.querySelector('input[name=imei]').value = code; document.querySelector('input[name=imei]').form.submit(); })" class="btn btn-outline" title="Scanner">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M3 7V5a2 2 0 012-2h2M17 3h2a2 2 0 012 2v2M21 17v2a2 2 0 01-2 2h-2M7 21H5a2 2 0 01-2-2v-2M7 8h10M7 12h10M7 16h10"/>
                </svg>
            </button>
            <button type="submit" class="btn btn-primary">Rechercher</button>
        </form>
    </div>

    <?php if ($searchImei && empty($results)): ?>
        <p class="text-muted text-center">Aucun résultat pour "<?= htmlspecialchars($searchImei) ?>"</p>
    <?php elseif (!empty($results)): ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>IMEI</th>
                        <th>Téléphone</th>
                        <th>Prix</th>
                        <th>Statut</th>
                        <th>Détails vente</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $r): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($r['imei']) ?></strong></td>
                            <td><?= htmlspecialchars(($r['brand_name'] ?? '') . ' ' . $r['model']) ?></td>
                            <td><?= number_format($r['price'], 0, ',', ' ') ?> Ar</td>
                            <td>
                                <?php if ($r['statut'] === 'vendu'): ?>
                                    <span class="badge badge-danger">Vendu</span>
                                <?php else: ?>
                                    <span class="badge badge-success">En stock</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($r['statut'] === 'vendu'): ?>
                                    <?= htmlspecialchars($r['client_name']) ?>
                                    - <?= htmlspecialchars($r['invoice_number']) ?>
                                    <br><small class="text-muted"><?= date('d/m/Y', strtotime($r['sale_date'])) ?></small>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="text-muted text-center mt-2"><?= count($results) ?> résultat(s)</p>
    <?php elseif (!$searchImei): ?>
        <p class="text-muted text-center">Entrez un numéro IMEI pour lancer la recherche</p>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
