<?php
$pageTitle = 'Ajustement de stock';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
requireLogin();

$visibleIds = getVisibleUserIds();
$uf = buildVisibleUserFilter();

$phoneId = intval($_GET['id'] ?? 0);
$selectedPhone = null;

if ($phoneId) {
    $selectedPhone = fetchOne(
        "SELECT p.*, b.name as brand_name FROM phones p LEFT JOIN brands b ON p.brand_id = b.id WHERE p.id = :id",
        ['id' => $phoneId]
    );
    if ($selectedPhone && !in_array($selectedPhone['user_id'], $visibleIds)) {
        $selectedPhone = null;
    }
}

$errors = [];
$success = false;
$postedImeis = [];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de sécurité invalide.';
    } else {
        $phoneId = intval($_POST['phone_id'] ?? 0);
        $type = $_POST['type'] ?? '';
        $quantity = intval($_POST['quantity'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        $postedImeis = $_POST['imeis'] ?? [];

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
        if (!$phone || !in_array($phone['user_id'], $visibleIds)) {
            $errors[] = 'Téléphone introuvable.';
        } elseif ($type === 'OUT' && $quantity > $phone['quantity']) {
            $errors[] = 'Stock insuffisant. Stock actuel : ' . $phone['quantity'];
        }

        // Valider les IMEI pour les entrées
        $cleanImeis = [];
        if (empty($errors) && $type === 'IN') {
            for ($i = 0; $i < $quantity; $i++) {
                $imei = trim($postedImeis[$i] ?? '');
                if (empty($imei)) {
                    $errors[] = "L'IMEI du téléphone " . ($i + 1) . " est obligatoire.";
                } elseif (!validateImei($imei)) {
                    $errors[] = "L'IMEI \"$imei\" (téléphone " . ($i + 1) . ") est invalide (15 chiffres requis, checksum Luhn).";
                } else {
                    $existing = fetchOne("SELECT id FROM phone_imeis WHERE imei = :imei", ['imei' => $imei]);
                    if ($existing) {
                        $errors[] = "L'IMEI \"$imei\" (téléphone " . ($i + 1) . ") existe déjà en base.";
                    }
                    $cleanImeis[] = $imei;
                }
            }
            if (count($cleanImeis) !== count(array_unique($cleanImeis))) {
                $errors[] = 'Des IMEI en doublon ont été saisis.';
            }
        }

        if (empty($errors)) {
            $pdo = getConnection();
            $pdo->beginTransaction();
            try {
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

                // Insérer les IMEI pour les entrées
                if ($type === 'IN') {
                    foreach ($cleanImeis as $imei) {
                        execute(
                            "INSERT INTO phone_imeis (phone_id, imei) VALUES (:phone_id, :imei)",
                            ['phone_id' => $phoneId, 'imei' => $imei]
                        );
                    }
                }

                $pdo->commit();
                $_SESSION['flash_message'] = 'Mouvement de stock enregistré avec succès.';
                $_SESSION['flash_type'] = 'success';
                header('Location: /pages/stock/movements.php');
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = "Erreur lors de l'enregistrement : " . $e->getMessage();
            }
        }
    }
}

// Récupérer les téléphones visibles
$phones = fetchAll(
    "SELECT p.id, p.model, p.quantity, b.name as brand_name
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
                        <input type="radio" name="type" value="IN" checked onchange="onTypeChange()">
                        <span class="badge badge-success" style="font-size: 1rem; padding: 0.5rem 1rem;">
                            Entrée (+)
                        </span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="radio" name="type" value="OUT" onchange="onTypeChange()">
                        <span class="badge badge-danger" style="font-size: 1rem; padding: 0.5rem 1rem;">
                            Sortie (-)
                        </span>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label for="quantity" class="form-label">Quantité *</label>
                <input type="number" id="quantity" name="quantity" class="form-control"
                       min="1" value="1" required oninput="generateImeiFields()">
            </div>
        </div>

        <div id="imei-container"></div>

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
const existingImeis = <?= json_encode(array_map('trim', $postedImeis)) ?>;

function getSelectedType() {
    const checked = document.querySelector('input[name="type"]:checked');
    return checked ? checked.value : 'IN';
}

function onTypeChange() {
    generateImeiFields();
}

function generateImeiFields() {
    const container = document.getElementById('imei-container');
    const type = getSelectedType();
    const qty = parseInt(document.getElementById('quantity').value) || 0;

    if (type !== 'IN' || qty <= 0) {
        container.innerHTML = '';
        return;
    }

    let html = '<div class="card" style="margin: 1rem 0; padding: 1rem; background: var(--bg-color);">';
    html += '<h3 style="font-size: 0.95rem; margin-bottom: 1rem;">IMEI des téléphones à ajouter</h3>';

    for (let i = 0; i < qty; i++) {
        const val = existingImeis[i] || '';
        html += `
            <div class="form-group">
                <label class="form-label">IMEI du téléphone ${i + 1} *</label>
                <div style="display: flex; gap: 0.5rem;">
                    <input type="text" name="imeis[]" class="form-control" required
                           value="${val.replace(/"/g, '&quot;')}" placeholder="Saisir ou scanner l'IMEI">
                    <button type="button" onclick="scanImei(this)" class="btn btn-outline">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M3 7V5a2 2 0 012-2h2M17 3h2a2 2 0 012 2v2M21 17v2a2 2 0 01-2 2h-2M7 21H5a2 2 0 01-2-2v-2M7 8h10M7 12h10M7 16h10"/>
                        </svg>
                    </button>
                </div>
            </div>`;
    }

    html += '</div>';
    container.innerHTML = html;
}

function scanImei(btn) {
    const input = btn.closest('div').querySelector('input');
    if (typeof openBarcodeScanner === 'function') {
        openBarcodeScanner(code => input.value = code);
    }
}

function updateStockInfo(select) {
    // Info stock handled by page reload / display
}

// Générer les champs au chargement
document.addEventListener('DOMContentLoaded', generateImeiFields);
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
