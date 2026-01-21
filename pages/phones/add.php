<?php
$pageTitle = 'Ajouter un téléphone';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireLogin();

$errors = [];
$data = [
    'brand_id' => '',
    'model' => '',
    'barcode' => '',
    'description' => '',
    'price' => '',
    'quantity' => '0',
    'min_stock' => '5'
];

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
            'quantity' => $_POST['quantity'] ?? '0',
            'min_stock' => $_POST['min_stock'] ?? '5'
        ];

        // Validation
        if (empty($data['model'])) {
            $errors[] = 'Le modèle est obligatoire.';
        }
        if (empty($data['price']) || !is_numeric($data['price']) || $data['price'] < 0) {
            $errors[] = 'Le prix doit être un nombre positif.';
        }
        if (!is_numeric($data['quantity']) || $data['quantity'] < 0) {
            $errors[] = 'La quantité doit être un nombre positif.';
        }

        if (empty($errors)) {
            $sql = "INSERT INTO phones (brand_id, model, barcode, description, price, quantity, min_stock)
                    VALUES (:brand_id, :model, :barcode, :description, :price, :quantity, :min_stock)";

            execute($sql, [
                'brand_id' => $data['brand_id'] ?: null,
                'model' => $data['model'],
                'barcode' => $data['barcode'] ?: null,
                'description' => $data['description'],
                'price' => $data['price'],
                'quantity' => $data['quantity'],
                'min_stock' => $data['min_stock']
            ]);

            // Enregistrer dans l'historique des prix
            $phoneId = lastInsertId();
            execute(
                "INSERT INTO price_history (phone_id, old_price, new_price, changed_by) VALUES (:phone_id, NULL, :price, :user_id)",
                ['phone_id' => $phoneId, 'price' => $data['price'], 'user_id' => $_SESSION['user_id']]
            );

            $_SESSION['flash_message'] = 'Téléphone ajouté avec succès.';
            $_SESSION['flash_type'] = 'success';
            header('Location: /pages/phones/list.php');
            exit;
        }
    }
}

// Récupérer les marques
$brands = fetchAll("SELECT * FROM brands ORDER BY name");

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title">Ajouter un téléphone</h1>
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
                       value="<?= htmlspecialchars($data['barcode']) ?>" placeholder="Scanner ou saisir le code">
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
                <label for="quantity" class="form-label">Quantité initiale</label>
                <input type="number" id="quantity" name="quantity" class="form-control"
                       min="0" value="<?= htmlspecialchars($data['quantity']) ?>">
            </div>

            <div class="form-group">
                <label for="min_stock" class="form-label">Stock minimum (alerte)</label>
                <input type="number" id="min_stock" name="min_stock" class="form-control"
                       min="0" value="<?= htmlspecialchars($data['min_stock']) ?>">
            </div>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn btn-primary">Ajouter le téléphone</button>
            <a href="/pages/phones/list.php" class="btn btn-outline">Annuler</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
