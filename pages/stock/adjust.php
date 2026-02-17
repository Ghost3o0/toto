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
        $status = ($type === 'OUT') ? ($_POST['status'] ?? 'en_attente') : 'confirme';
        if (!in_array($status, ['en_attente', 'confirme'])) {
            $status = 'en_attente';
        }

        // Validation
        if (!$phoneId) {
            $errors[] = 'Veuillez sélectionner un téléphone.';
        }
        if (!in_array($type, ['IN', 'OUT'])) {
            $errors[] = 'Type de mouvement invalide.';
        }
        if ($type === 'IN' && $quantity <= 0) {
            $errors[] = 'La quantité doit être supérieure à 0.';
        }

        // Vérifier le téléphone
        $phone = fetchOne("SELECT * FROM phones WHERE id = :id", ['id' => $phoneId]);
        if (!$phone || !in_array($phone['user_id'], $visibleIds)) {
            $errors[] = 'Téléphone introuvable.';
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

        // Valider les IMEI sélectionnés pour les sorties
        $selectedImeiIds = [];
        if (empty($errors) && $type === 'OUT') {
            $selectedImeiIds = array_filter(array_map('intval', $_POST['selected_imeis'] ?? []));
            // Vérifier les doublons
            if (count($selectedImeiIds) !== count(array_unique($selectedImeiIds))) {
                $errors[] = 'Des IMEI en doublon ont été sélectionnés.';
            }
            if (empty($selectedImeiIds)) {
                $errors[] = 'Veuillez sélectionner au moins un IMEI à sortir.';
            } else {
                // Vérifier que chaque IMEI appartient au téléphone et est en stock
                foreach ($selectedImeiIds as $imeiId) {
                    $imeiRow = fetchOne(
                        "SELECT id FROM phone_imeis WHERE id = :id AND phone_id = :pid AND status = 'in_stock'",
                        ['id' => $imeiId, 'pid' => $phoneId]
                    );
                    if (!$imeiRow) {
                        $errors[] = "IMEI invalide ou déjà sorti (ID: $imeiId).";
                    }
                }
                // La quantité est déterminée par le nombre d'IMEI sélectionnés
                $quantity = count($selectedImeiIds);
            }
        }

        if (empty($errors)) {
            $pdo = getConnection();
            $pdo->beginTransaction();
            try {
                // Enregistrer le mouvement
                execute(
                    "INSERT INTO stock_movements (phone_id, user_id, type, quantity, reason, status) VALUES (:phone_id, :user_id, :type, :quantity, :reason, :status)",
                    [
                        'phone_id' => $phoneId,
                        'user_id' => $_SESSION['user_id'],
                        'type' => $type,
                        'quantity' => $quantity,
                        'reason' => $reason ?: null,
                        'status' => $status
                    ]
                );
                $movementId = lastInsertId();

                // Mettre à jour le stock
                $newQuantity = $type === 'IN' ? $phone['quantity'] + $quantity : $phone['quantity'] - $quantity;
                if ($newQuantity < 0) {
                    throw new Exception("Stock insuffisant. Stock actuel : {$phone['quantity']}, quantité demandée : $quantity.");
                }
                execute(
                    "UPDATE phones SET quantity = :quantity, updated_at = CURRENT_TIMESTAMP WHERE id = :id",
                    ['quantity' => $newQuantity, 'id' => $phoneId]
                );

                // Insérer les IMEI pour les entrées et les lier au mouvement
                if ($type === 'IN') {
                    foreach ($cleanImeis as $imei) {
                        execute(
                            "INSERT INTO phone_imeis (phone_id, imei) VALUES (:phone_id, :imei)",
                            ['phone_id' => $phoneId, 'imei' => $imei]
                        );
                        $imeiId = lastInsertId();
                        execute(
                            "INSERT INTO stock_movement_imeis (movement_id, phone_imei_id) VALUES (:mid, :iid)",
                            ['mid' => $movementId, 'iid' => $imeiId]
                        );
                    }
                }

                // Marquer les IMEI comme sortis pour les sorties
                if ($type === 'OUT') {
                    foreach ($selectedImeiIds as $imeiId) {
                        execute(
                            "UPDATE phone_imeis SET status = 'sold' WHERE id = :id",
                            ['id' => $imeiId]
                        );
                        execute(
                            "INSERT INTO stock_movement_imeis (movement_id, phone_imei_id) VALUES (:mid, :iid)",
                            ['mid' => $movementId, 'iid' => $imeiId]
                        );
                    }

                    // Si le mouvement OUT est directement confirmé, créer une facture
                    if ($status === 'confirme') {
                        $phoneInfo = fetchOne(
                            "SELECT p.model, p.price, b.name as brand_name FROM phones p LEFT JOIN brands b ON p.brand_id = b.id WHERE p.id = :id",
                            ['id' => $phoneId]
                        );
                        $invoiceNumber = generateInvoiceNumber();
                        $unitPrice = $phoneInfo['price'] ?? 0;
                        $totalAmount = $unitPrice * $quantity;

                        execute(
                            "INSERT INTO invoices (invoice_number, user_id, client_name, total_amount, notes) VALUES (:num, :uid, :name, :total, :notes)",
                            [
                                'num' => $invoiceNumber,
                                'uid' => $_SESSION['user_id'],
                                'name' => 'Client (mouvement stock)',
                                'total' => $totalAmount,
                                'notes' => $reason ?: null
                            ]
                        );
                        $invoiceId = lastInsertId();

                        execute(
                            "INSERT INTO invoice_lines (invoice_id, phone_id, phone_model, phone_brand, quantity, unit_price, line_total) VALUES (:iid, :pid, :model, :brand, :qty, :price, :total)",
                            [
                                'iid' => $invoiceId,
                                'pid' => $phoneId,
                                'model' => $phoneInfo['model'] ?? '',
                                'brand' => $phoneInfo['brand_name'] ?? '',
                                'qty' => $quantity,
                                'price' => $unitPrice,
                                'total' => $totalAmount
                            ]
                        );
                        $invoiceLineId = lastInsertId();

                        foreach ($selectedImeiIds as $imeiId) {
                            execute(
                                "INSERT INTO invoice_line_imeis (invoice_line_id, phone_imei_id) VALUES (:line_id, :imei_id)",
                                ['line_id' => $invoiceLineId, 'imei_id' => $imeiId]
                            );
                        }
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

// Récupérer les IMEI disponibles par téléphone (pour les sorties)
$imeisPerPhone = [];
foreach ($phones as $p) {
    $imeis = fetchAll(
        "SELECT id, imei FROM phone_imeis WHERE phone_id = :pid AND status = 'in_stock' ORDER BY created_at",
        ['pid' => $p['id']]
    );
    $imeisPerPhone[$p['id']] = $imeis;
}

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

    <form method="POST" action="" id="adjust-form">
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

            <div class="form-group" id="quantity-group">
                <label for="quantity" class="form-label">Quantité *</label>
                <input type="number" id="quantity" name="quantity" class="form-control"
                       min="1" value="1" required oninput="generateImeiFields()">
            </div>
        </div>

        <div class="form-group" id="status-group" style="display: none;">
            <label class="form-label">Statut du mouvement *</label>
            <div style="display: flex; gap: 1rem; margin-top: 0.5rem;">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="radio" name="status" value="en_attente" checked>
                    <span class="badge badge-warning" style="font-size: 0.9rem; padding: 0.4rem 0.8rem;">
                        En attente de paiement
                    </span>
                </label>
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="radio" name="status" value="confirme">
                    <span class="badge badge-success" style="font-size: 0.9rem; padding: 0.4rem 0.8rem;">
                        Paiement confirme
                    </span>
                </label>
            </div>
        </div>

        <div id="imei-container"></div>
        <div id="imei-out-container"></div>

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
const imeisData = <?= json_encode($imeisPerPhone) ?>;

function getSelectedType() {
    const checked = document.querySelector('input[name="type"]:checked');
    return checked ? checked.value : 'IN';
}

function onTypeChange() {
    const type = getSelectedType();
    const qtyGroup = document.getElementById('quantity-group');
    const qtyInput = document.getElementById('quantity');
    const statusGroup = document.getElementById('status-group');

    if (type === 'OUT') {
        qtyGroup.style.display = 'none';
        qtyInput.removeAttribute('required');
        statusGroup.style.display = '';
    } else {
        qtyGroup.style.display = '';
        qtyInput.setAttribute('required', 'required');
        statusGroup.style.display = 'none';
    }

    generateImeiFields();
    generateOutImeiCheckboxes();
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

function generateOutImeiCheckboxes() {
    const container = document.getElementById('imei-out-container');
    const type = getSelectedType();
    const phoneSelect = document.getElementById('phone_id');
    const phoneId = phoneSelect.value;

    if (type !== 'OUT' || !phoneId) {
        container.innerHTML = '';
        return;
    }

    const imeis = imeisData[phoneId] || [];

    if (imeis.length === 0) {
        container.innerHTML = '<div class="alert alert-info" style="margin: 1rem 0;">Aucun IMEI en stock pour ce téléphone.</div>';
        return;
    }

    let html = '<div class="card" style="margin: 1rem 0; padding: 1rem; background: var(--bg-color);">';
    html += '<h3 style="font-size: 0.95rem; margin-bottom: 0.5rem;">Sélectionner les IMEI à sortir</h3>';
    html += '<p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">' + imeis.length + ' IMEI disponible(s) — <span id="out-imei-count">0</span> sélectionné(s)</p>';
    html += '<div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">';

    imeis.forEach(imei => {
        html += `
            <label style="display: flex; align-items: center; gap: 0.3rem; padding: 0.4rem 0.8rem; border: 1px solid var(--border-color); border-radius: var(--radius); cursor: pointer; font-size: 0.85rem; transition: all 0.2s;">
                <input type="checkbox" name="selected_imeis[]" value="${imei.id}" onchange="updateOutImeiCount()">
                <code>${imei.imei}</code>
            </label>`;
    });

    html += '</div></div>';
    container.innerHTML = html;
}

function updateOutImeiCount() {
    const checked = document.querySelectorAll('#imei-out-container input[type="checkbox"]:checked');
    const countSpan = document.getElementById('out-imei-count');
    if (countSpan) {
        countSpan.textContent = checked.length;
    }
    // Mettre à jour la quantité cachée
    document.getElementById('quantity').value = checked.length || 1;
}

function scanImei(btn) {
    const input = btn.closest('div').querySelector('input');
    if (typeof openBarcodeScanner === 'function') {
        openBarcodeScanner(code => input.value = code);
    }
}

function updateStockInfo(select) {
    // Regénérer les checkboxes IMEI si on est en mode sortie
    if (getSelectedType() === 'OUT') {
        generateOutImeiCheckboxes();
    }
}

// Protection double-soumission
document.getElementById('adjust-form').addEventListener('submit', function(event) {
    const btn = this.querySelector('button[type="submit"]');
    if (btn.disabled) { event.preventDefault(); return; }
    btn.disabled = true;
    btn.textContent = 'Enregistrement...';
});

// Générer les champs au chargement
document.addEventListener('DOMContentLoaded', function() {
    onTypeChange();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
