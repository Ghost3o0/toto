<?php
$pageTitle = 'Ajustement de stock';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireLogin();

$phoneId = intval($_GET['id'] ?? 0);
$selectedPhone = null;

if ($phoneId) {
    $selectedPhone = fetchOne(
        "SELECT p.*, b.name as brand_name FROM phones p LEFT JOIN brands b ON p.brand_id = b.id WHERE p.id = :id",
        ['id' => $phoneId]
    );
}

$errors = [];
$success = false;

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de sécurité invalide.';
    } else {
        $phoneId = intval($_POST['phone_id'] ?? 0);
        $type = $_POST['type'] ?? '';
        $quantity = intval($_POST['quantity'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');

        // Validation
        if (!$phoneId) {
            $errors[] = 'Veuillez sélectionner un téléphone.';
        }
        if (!in_array($type, ['IN', 'OUT'])) {
            $errors[] = 'Type de mouvement invalide.';
        }
        if ($quantity <= 0) {
            $errors[] = 'La quantité doit être supérieure à 0.';
        }

        // Vérifier le téléphone
        $phone = fetchOne("SELECT * FROM phones WHERE id = :id", ['id' => $phoneId]);
        if (!$phone) {
            $errors[] = 'Téléphone introuvable.';
        } elseif ($type === 'OUT' && $quantity > $phone['quantity']) {
            $errors[] = 'Stock insuffisant. Stock actuel : ' . $phone['quantity'];
        }

        if (empty($errors)) {
            // Enregistrer le mouvement
            execute(
                "INSERT INTO stock_movements (phone_id, user_id, type, quantity, reason) VALUES (:phone_id, :user_id, :type, :quantity, :reason)",
                [
                    'phone_id' => $phoneId,
                    'user_id' => $_SESSION['user_id'],
                    'type' => $type,
                    'quantity' => $quantity,
                    'reason' => $reason ?: null
                ]
            );

            // Mettre à jour le stock
            $newQuantity = $type === 'IN' ? $phone['quantity'] + $quantity : $phone['quantity'] - $quantity;
            execute(
                "UPDATE phones SET quantity = :quantity, updated_at = CURRENT_TIMESTAMP WHERE id = :id",
                ['quantity' => $newQuantity, 'id' => $phoneId]
            );

            $_SESSION['flash_message'] = 'Mouvement de stock enregistré avec succès.';
            $_SESSION['flash_type'] = 'success';
            header('Location: /pages/stock/movements.php');
            exit;
        }
    }
}

// Récupérer tous les téléphones
$phones = fetchAll(
    "SELECT p.id, p.model, p.quantity, b.name as brand_name
     FROM phones p
     LEFT JOIN brands b ON p.brand_id = b.id
     ORDER BY b.name, p.model"
);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title">
            <?php if ($selectedPhone): ?>
                Ajuster le stock : <?= htmlspecialchars($selectedPhone['brand_name'] . ' ' . $selectedPhone['model']) ?>
            <?php else: ?>
                Nouveau mouvement de stock
            <?php endif; ?>
        </h1>
        <a href="/pages/stock/movements.php" class="btn btn-outline">Voir l'historique</a>
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

    <?php if ($selectedPhone): ?>
        <div class="alert alert-info">
            <strong>Stock actuel :</strong> <?= $selectedPhone['quantity'] ?> unité(s)
            <?php if ($selectedPhone['quantity'] <= ($selectedPhone['min_stock'] ?? 5)): ?>
                <span class="badge badge-danger">Stock bas</span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <?php csrfField(); ?>

        <div class="form-group">
            <label for="phone_id" class="form-label">Téléphone *</label>
            <select id="phone_id" name="phone_id" class="form-control" required onchange="updateStockInfo(this)">
                <option value="">Sélectionner un téléphone</option>
                <?php foreach ($phones as $phone): ?>
                    <option value="<?= $phone['id'] ?>" data-quantity="<?= $phone['quantity'] ?>"
                            <?= ($selectedPhone && $selectedPhone['id'] == $phone['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($phone['brand_name'] . ' - ' . $phone['model']) ?>
                        (Stock: <?= $phone['quantity'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Type de mouvement *</label>
                <div style="display: flex; gap: 1rem; margin-top: 0.5rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="radio" name="type" value="IN" checked>
                        <span class="badge badge-success" style="font-size: 1rem; padding: 0.5rem 1rem;">
                            Entrée (+)
                        </span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="radio" name="type" value="OUT">
                        <span class="badge badge-danger" style="font-size: 1rem; padding: 0.5rem 1rem;">
                            Sortie (-)
                        </span>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label for="quantity" class="form-label">Quantité *</label>
                <input type="number" id="quantity" name="quantity" class="form-control"
                       min="1" value="1" required>
            </div>
        </div>

        <div class="form-group">
            <label for="reason" class="form-label">Raison / Commentaire</label>
            <input type="text" id="reason" name="reason" class="form-control"
                   placeholder="Ex: Réception commande fournisseur, Vente client, Retour SAV...">
        </div>

        <div class="btn-group">
            <button type="submit" class="btn btn-primary">Enregistrer le mouvement</button>
            <a href="/pages/phones/list.php" class="btn btn-outline">Annuler</a>
        </div>
    </form>
</div>

<script>
function updateStockInfo(select) {
    const option = select.options[select.selectedIndex];
    const quantity = option.dataset.quantity;
    if (quantity !== undefined) {
        // Mettre à jour l'info si nécessaire
    }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
