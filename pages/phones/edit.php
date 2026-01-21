<?php
$pageTitle = 'Modifier un téléphone';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireLogin();

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header('Location: /pages/phones/list.php');
    exit;
}

// Récupérer le téléphone
$phone = fetchOne("SELECT * FROM phones WHERE id = :id", ['id' => $id]);
if (!$phone) {
    $_SESSION['flash_message'] = 'Téléphone introuvable.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: /pages/phones/list.php');
    exit;
}

$errors = [];
$data = $phone;

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de sécurité invalide.';
    } else {
        $data = [
            'brand_id' => $_POST['brand_id'] ?? '',
            'model' => trim($_POST['model'] ?? ''),
            'barcode' => trim($_POST['barcode'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'price' => $_POST['price'] ?? '',
            'min_stock' => $_POST['min_stock'] ?? '5'
        ];

        // Validation
        if (empty($data['model'])) {
            $errors[] = 'Le modèle est obligatoire.';
        }
        if (empty($data['price']) || !is_numeric($data['price']) || $data['price'] < 0) {
            $errors[] = 'Le prix doit être un nombre positif.';
        }

        if (empty($errors)) {
            // Vérifier si le prix a changé pour l'historique
            $oldPrice = $phone['price'];
            $newPrice = $data['price'];

            $sql = "UPDATE phones SET
                    brand_id = :brand_id,
                    model = :model,
                    barcode = :barcode,
                    description = :description,
                    price = :price,
                    min_stock = :min_stock,
                    updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id";

            execute($sql, [
                'id' => $id,
                'brand_id' => $data['brand_id'] ?: null,
                'model' => $data['model'],
                'barcode' => $data['barcode'] ?: null,
                'description' => $data['description'],
                'price' => $newPrice,
                'min_stock' => $data['min_stock']
            ]);

            // Enregistrer dans l'historique si le prix a changé
            if ($oldPrice != $newPrice) {
                execute(
                    "INSERT INTO price_history (phone_id, old_price, new_price, changed_by) VALUES (:phone_id, :old_price, :new_price, :user_id)",
                    ['phone_id' => $id, 'old_price' => $oldPrice, 'new_price' => $newPrice, 'user_id' => $_SESSION['user_id']]
                );
            }

            $_SESSION['flash_message'] = 'Téléphone modifié avec succès.';
            $_SESSION['flash_type'] = 'success';
            header('Location: /pages/phones/list.php');
            exit;
        }
    }
}

// Récupérer les marques
$brands = fetchAll("SELECT * FROM brands ORDER BY name");

// Récupérer l'historique des prix
$priceHistory = fetchAll(
    "SELECT ph.*, u.username FROM price_history ph
     LEFT JOIN users u ON ph.changed_by = u.id
     WHERE ph.phone_id = :phone_id
     ORDER BY ph.changed_at DESC
     LIMIT 10",
    ['phone_id' => $id]
);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title">Modifier : <?= htmlspecialchars($phone['model']) ?></h1>
        <a href="/pages/phones/list.php" class="btn btn-outline">Retour à la liste</a>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <?php csrfField(); ?>

        <div class="form-row">
            <div class="form-group">
                <label for="brand_id" class="form-label">Marque</label>
                <select id="brand_id" name="brand_id" class="form-control">
                    <option value="">Sélectionner une marque</option>
                    <?php foreach ($brands as $brand): ?>
                        <option value="<?= $brand['id'] ?>" <?= $data['brand_id'] == $brand['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($brand['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="model" class="form-label">Modèle *</label>
                <input type="text" id="model" name="model" class="form-control"
                       value="<?= htmlspecialchars($data['model']) ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label for="barcode" class="form-label">Code-barres / IMEI</label>
            <div style="display: flex; gap: 0.5rem;">
                <input type="text" id="barcode" name="barcode" class="form-control"
                       value="<?= htmlspecialchars($data['barcode'] ?? '') ?>" placeholder="Scanner ou saisir le code">
                <button type="button" onclick="openBarcodeScanner(code => document.getElementById('barcode').value = code)" class="btn btn-outline">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M3 7V5a2 2 0 012-2h2M17 3h2a2 2 0 012 2v2M21 17v2a2 2 0 01-2 2h-2M7 21H5a2 2 0 01-2-2v-2M7 8h10M7 12h10M7 16h10"/>
                    </svg>
                </button>
            </div>
        </div>

        <div class="form-group">
            <label for="description" class="form-label">Description</label>
            <textarea id="description" name="description" class="form-control"
                      placeholder="Description du produit..."><?= htmlspecialchars($data['description']) ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="price" class="form-label">Prix (€) *</label>
                <input type="number" id="price" name="price" class="form-control"
                       step="0.01" min="0" value="<?= htmlspecialchars($data['price']) ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Quantité actuelle</label>
                <input type="text" class="form-control" value="<?= $phone['quantity'] ?>" disabled>
                <small class="text-muted">Utilisez la gestion de stock pour modifier la quantité</small>
            </div>

            <div class="form-group">
                <label for="min_stock" class="form-label">Stock minimum (alerte)</label>
                <input type="number" id="min_stock" name="min_stock" class="form-control"
                       min="0" value="<?= htmlspecialchars($data['min_stock']) ?>">
            </div>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
            <a href="/pages/stock/adjust.php?id=<?= $id ?>" class="btn btn-success">Gérer le stock</a>
            <a href="/pages/phones/list.php" class="btn btn-outline">Annuler</a>
        </div>
    </form>
</div>

<?php if (!empty($priceHistory)): ?>
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Historique des prix</h2>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Ancien prix</th>
                    <th>Nouveau prix</th>
                    <th>Variation</th>
                    <th>Modifié par</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($priceHistory as $history): ?>
                    <?php
                    $variation = $history['old_price'] ? $history['new_price'] - $history['old_price'] : 0;
                    $variationPercent = $history['old_price'] ? round(($variation / $history['old_price']) * 100, 1) : 0;
                    ?>
                    <tr>
                        <td><?= date('d/m/Y H:i', strtotime($history['changed_at'])) ?></td>
                        <td><?= $history['old_price'] ? number_format($history['old_price'], 2, ',', ' ') . ' €' : '-' ?></td>
                        <td><strong><?= number_format($history['new_price'], 2, ',', ' ') ?> €</strong></td>
                        <td>
                            <?php if ($variation > 0): ?>
                                <span class="badge badge-danger">+<?= number_format($variation, 2, ',', ' ') ?> € (+<?= $variationPercent ?>%)</span>
                            <?php elseif ($variation < 0): ?>
                                <span class="badge badge-success"><?= number_format($variation, 2, ',', ' ') ?> € (<?= $variationPercent ?>%)</span>
                            <?php else: ?>
                                <span class="badge badge-info">Prix initial</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($history['username'] ?? 'Système') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
