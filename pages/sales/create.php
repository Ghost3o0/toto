<?php
$pageTitle = 'Nouvelle vente';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
requireLogin();

$uf = buildVisibleUserFilter();
$errors = [];

// Récupérer les téléphones disponibles (stock > 0)
$availablePhones = fetchAll(
    "SELECT p.id, p.model, p.price, p.quantity, b.name as brand_name
     FROM phones p
     LEFT JOIN brands b ON p.brand_id = b.id
     WHERE p.user_id IN ({$uf['placeholders']}) AND p.quantity > 0
     ORDER BY b.name, p.model",
    $uf['params']
);

// Récupérer les IMEI disponibles par téléphone
$imeisPerPhone = [];
foreach ($availablePhones as $phone) {
    $imeis = fetchAll(
        "SELECT id, imei FROM phone_imeis WHERE phone_id = :pid AND status = 'in_stock' ORDER BY created_at",
        ['pid' => $phone['id']]
    );
    $imeisPerPhone[$phone['id']] = $imeis;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de sécurité invalide.';
    } else {
        $clientName = trim($_POST['client_name'] ?? '');
        $clientPhone = trim($_POST['client_phone'] ?? '');
        $clientAddress = trim($_POST['client_address'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $linePhoneIds = $_POST['line_phone_id'] ?? [];
        $linePrices = $_POST['line_price'] ?? [];
        $lineImeiIds = $_POST['line_imeis'] ?? []; // array of arrays

        if (empty($clientName)) {
            $errors[] = 'Le nom du client est obligatoire.';
        }
        if (empty($linePhoneIds) || empty($linePhoneIds[0])) {
            $errors[] = 'Ajoutez au moins une ligne de vente.';
        }

        // Valider chaque ligne
        $validLines = [];
        if (empty($errors)) {
            for ($i = 0; $i < count($linePhoneIds); $i++) {
                $pid = intval($linePhoneIds[$i] ?? 0);
                $price = floatval($linePrices[$i] ?? 0);
                $selectedImeiIds = $lineImeiIds[$i] ?? [];

                if (!$pid) continue;

                // Filtrer les IMEI valides
                $selectedImeiIds = array_filter(array_map('intval', $selectedImeiIds));
                $qty = count($selectedImeiIds);

                if ($qty <= 0) {
                    $errors[] = "Ligne " . ($i + 1) . " : sélectionnez au moins un IMEI.";
                    continue;
                }
                if ($price < 0) {
                    $errors[] = "Ligne " . ($i + 1) . " : le prix ne peut pas être négatif.";
                    continue;
                }

                $phone = fetchOne("SELECT p.*, b.name as brand_name FROM phones p LEFT JOIN brands b ON p.brand_id = b.id WHERE p.id = :id", ['id' => $pid]);
                if (!$phone || !in_array($phone['user_id'], $uf['ids'])) {
                    $errors[] = "Ligne " . ($i + 1) . " : téléphone introuvable.";
                    continue;
                }
                if ($qty > $phone['quantity']) {
                    $errors[] = "Ligne " . ($i + 1) . " : stock insuffisant pour " . $phone['model'] . " (disponible: " . $phone['quantity'] . ").";
                    continue;
                }

                // Vérifier que les IMEI appartiennent bien au téléphone et sont en stock
                foreach ($selectedImeiIds as $imeiId) {
                    $imeiRow = fetchOne(
                        "SELECT id FROM phone_imeis WHERE id = :id AND phone_id = :pid AND status = 'in_stock'",
                        ['id' => $imeiId, 'pid' => $pid]
                    );
                    if (!$imeiRow) {
                        $errors[] = "Ligne " . ($i + 1) . " : IMEI invalide ou déjà vendu.";
                        break;
                    }
                }

                $validLines[] = [
                    'phone_id' => $pid,
                    'phone_model' => $phone['model'],
                    'phone_brand' => $phone['brand_name'] ?? '',
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'line_total' => $qty * $price,
                    'imei_ids' => $selectedImeiIds
                ];
            }
        }

        if (empty($errors) && !empty($validLines)) {
            $pdo = getConnection();
            $pdo->beginTransaction();
            try {
                $invoiceNumber = generateInvoiceNumber();
                $totalAmount = array_sum(array_column($validLines, 'line_total'));

                // INSERT facture
                execute(
                    "INSERT INTO invoices (invoice_number, user_id, client_name, client_phone, client_address, total_amount, notes)
                     VALUES (:num, :uid, :name, :phone, :address, :total, :notes)",
                    [
                        'num' => $invoiceNumber,
                        'uid' => $_SESSION['user_id'],
                        'name' => $clientName,
                        'phone' => $clientPhone ?: null,
                        'address' => $clientAddress ?: null,
                        'total' => $totalAmount,
                        'notes' => $notes ?: null
                    ]
                );
                $invoiceId = lastInsertId();

                // INSERT lignes + mouvements stock + IMEI
                foreach ($validLines as $line) {
                    execute(
                        "INSERT INTO invoice_lines (invoice_id, phone_id, phone_model, phone_brand, quantity, unit_price, line_total)
                         VALUES (:iid, :pid, :model, :brand, :qty, :price, :total)",
                        [
                            'iid' => $invoiceId,
                            'pid' => $line['phone_id'],
                            'model' => $line['phone_model'],
                            'brand' => $line['phone_brand'],
                            'qty' => $line['quantity'],
                            'price' => $line['unit_price'],
                            'total' => $line['line_total']
                        ]
                    );
                    $invoiceLineId = lastInsertId();

                    // Marquer les IMEI comme vendus et créer les liaisons
                    foreach ($line['imei_ids'] as $imeiId) {
                        execute(
                            "UPDATE phone_imeis SET status = 'sold' WHERE id = :id",
                            ['id' => $imeiId]
                        );
                        execute(
                            "INSERT INTO invoice_line_imeis (invoice_line_id, phone_imei_id) VALUES (:line_id, :imei_id)",
                            ['line_id' => $invoiceLineId, 'imei_id' => $imeiId]
                        );
                    }

                    // Mouvement de stock OUT
                    execute(
                        "INSERT INTO stock_movements (phone_id, user_id, type, quantity, reason)
                         VALUES (:pid, :uid, 'OUT', :qty, :reason)",
                        [
                            'pid' => $line['phone_id'],
                            'uid' => $_SESSION['user_id'],
                            'qty' => $line['quantity'],
                            'reason' => "Vente $invoiceNumber"
                        ]
                    );

                    // Mettre à jour le stock
                    execute(
                        "UPDATE phones SET quantity = quantity - :qty, updated_at = CURRENT_TIMESTAMP WHERE id = :id",
                        ['qty' => $line['quantity'], 'id' => $line['phone_id']]
                    );
                }

                $pdo->commit();
                $_SESSION['flash_message'] = "Vente $invoiceNumber enregistrée avec succès.";
                $_SESSION['flash_type'] = 'success';
                header("Location: /pages/sales/view.php?id=$invoiceId");
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = "Erreur lors de l'enregistrement : " . $e->getMessage();
            }
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title">Nouvelle vente</h1>
        <a href="/pages/sales/list.php" class="btn btn-outline">Retour aux ventes</a>
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

    <form method="POST" action="" id="sale-form">
        <?php csrfField(); ?>

        <h2 style="font-size: 1rem; margin-bottom: 1rem;">Informations client</h2>
        <div class="form-row">
            <div class="form-group">
                <label for="client_name" class="form-label">Nom du client *</label>
                <input type="text" id="client_name" name="client_name" class="form-control"
                       value="<?= htmlspecialchars($_POST['client_name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="client_phone" class="form-label">Téléphone</label>
                <input type="text" id="client_phone" name="client_phone" class="form-control"
                       value="<?= htmlspecialchars($_POST['client_phone'] ?? '') ?>">
            </div>
        </div>
        <div class="form-group">
            <label for="client_address" class="form-label">Adresse</label>
            <input type="text" id="client_address" name="client_address" class="form-control"
                   value="<?= htmlspecialchars($_POST['client_address'] ?? '') ?>">
        </div>

        <h2 style="font-size: 1rem; margin: 1.5rem 0 1rem;">Articles</h2>
        <div id="lines-container">
            <div class="sale-line card" style="padding: 1rem; margin-bottom: 1rem; background: var(--bg-color);" data-line-index="0">
                <div class="form-row">
                    <div class="form-group" style="flex: 2;">
                        <label class="form-label">Téléphone</label>
                        <select name="line_phone_id[]" class="form-control phone-select" onchange="onPhoneSelect(this)" required>
                            <option value="">Sélectionner...</option>
                            <?php foreach ($availablePhones as $phone): ?>
                                <option value="<?= $phone['id'] ?>" data-price="<?= $phone['price'] ?>" data-stock="<?= $phone['quantity'] ?>">
                                    <?= htmlspecialchars(($phone['brand_name'] ?? '') . ' ' . $phone['model']) ?> (Stock: <?= $phone['quantity'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label">Prix unit.</label>
                        <input type="number" name="line_price[]" class="form-control line-price" min="0" step="0.01" value="0" onchange="updateLineTotals()">
                    </div>
                    <div class="form-group" style="flex: 0 0 80px;">
                        <label class="form-label">Qté</label>
                        <input type="text" class="form-control line-qty-display" value="0" disabled>
                    </div>
                    <div class="form-group" style="flex: 0 0 120px;">
                        <label class="form-label">Total</label>
                        <div class="line-total-cell" style="padding: 0.5rem 0; font-weight: bold;">0 Ar</div>
                    </div>
                    <div class="form-group" style="flex: 0 0 40px; display: flex; align-items: flex-end;">
                        <button type="button" class="btn btn-sm btn-danger" onclick="removeLine(this)">X</button>
                    </div>
                </div>
                <div class="imei-checkboxes" style="margin-top: 0.5rem;"></div>
            </div>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin: 1rem 0;">
            <button type="button" class="btn btn-outline" onclick="addLine()">
                + Ajouter une ligne
            </button>
            <div style="font-size: 1.2rem;">
                <strong>Total général : <span id="grand-total">0 Ar</span></strong>
            </div>
        </div>

        <div class="form-group">
            <label for="notes" class="form-label">Notes</label>
            <textarea id="notes" name="notes" class="form-control" rows="2"
                      placeholder="Notes ou commentaires..."><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn btn-success">Enregistrer la vente</button>
            <a href="/pages/sales/list.php" class="btn btn-outline">Annuler</a>
        </div>
    </form>
</div>

<script>
const phonesData = <?= json_encode($availablePhones) ?>;
const imeisData = <?= json_encode($imeisPerPhone) ?>;
let lineCounter = 1;

function getPhoneOptionsHtml() {
    let html = '<option value="">Sélectionner...</option>';
    phonesData.forEach(p => {
        html += `<option value="${p.id}" data-price="${p.price}" data-stock="${p.quantity}">${(p.brand_name || '')} ${p.model} (Stock: ${p.quantity})</option>`;
    });
    return html;
}

function addLine() {
    const container = document.getElementById('lines-container');
    const div = document.createElement('div');
    div.className = 'sale-line card';
    div.style.cssText = 'padding: 1rem; margin-bottom: 1rem; background: var(--bg-color);';
    div.dataset.lineIndex = lineCounter;
    div.innerHTML = `
        <div class="form-row">
            <div class="form-group" style="flex: 2;">
                <label class="form-label">Téléphone</label>
                <select name="line_phone_id[]" class="form-control phone-select" onchange="onPhoneSelect(this)" required>${getPhoneOptionsHtml()}</select>
            </div>
            <div class="form-group" style="flex: 1;">
                <label class="form-label">Prix unit.</label>
                <input type="number" name="line_price[]" class="form-control line-price" min="0" step="0.01" value="0" onchange="updateLineTotals()">
            </div>
            <div class="form-group" style="flex: 0 0 80px;">
                <label class="form-label">Qté</label>
                <input type="text" class="form-control line-qty-display" value="0" disabled>
            </div>
            <div class="form-group" style="flex: 0 0 120px;">
                <label class="form-label">Total</label>
                <div class="line-total-cell" style="padding: 0.5rem 0; font-weight: bold;">0 Ar</div>
            </div>
            <div class="form-group" style="flex: 0 0 40px; display: flex; align-items: flex-end;">
                <button type="button" class="btn btn-sm btn-danger" onclick="removeLine(this)">X</button>
            </div>
        </div>
        <div class="imei-checkboxes" style="margin-top: 0.5rem;"></div>
    `;
    container.appendChild(div);
    lineCounter++;
}

function removeLine(btn) {
    const lines = document.querySelectorAll('#lines-container .sale-line');
    if (lines.length > 1) {
        btn.closest('.sale-line').remove();
        updateLineTotals();
    }
}

function onPhoneSelect(select) {
    const option = select.options[select.selectedIndex];
    const lineDiv = select.closest('.sale-line');
    const priceInput = lineDiv.querySelector('.line-price');
    const imeiContainer = lineDiv.querySelector('.imei-checkboxes');
    const lineIndex = lineDiv.dataset.lineIndex;

    if (option.value) {
        priceInput.value = parseFloat(option.dataset.price).toFixed(2);
        const phoneId = option.value;
        const imeis = imeisData[phoneId] || [];

        if (imeis.length > 0) {
            let html = '<div style="padding: 0.5rem; border: 1px solid var(--border-color); border-radius: var(--radius);">';
            html += '<label class="form-label" style="margin-bottom: 0.5rem;">Sélectionner les IMEI à vendre :</label>';
            html += '<div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">';
            imeis.forEach(imei => {
                html += `
                    <label style="display: flex; align-items: center; gap: 0.3rem; padding: 0.3rem 0.6rem; border: 1px solid var(--border-color); border-radius: var(--radius); cursor: pointer; font-size: 0.85rem;">
                        <input type="checkbox" name="line_imeis[${lineIndex}][]" value="${imei.id}" onchange="updateImeiCount(this)">
                        <code>${imei.imei}</code>
                    </label>`;
            });
            html += '</div></div>';
            imeiContainer.innerHTML = html;
        } else {
            imeiContainer.innerHTML = '<small class="text-muted">Aucun IMEI enregistré pour ce téléphone.</small>';
        }
    } else {
        priceInput.value = '0';
        imeiContainer.innerHTML = '';
    }
    updateImeiCountForLine(lineDiv);
    updateLineTotals();
}

function updateImeiCount(checkbox) {
    const lineDiv = checkbox.closest('.sale-line');
    updateImeiCountForLine(lineDiv);
    updateLineTotals();
}

function updateImeiCountForLine(lineDiv) {
    const checked = lineDiv.querySelectorAll('.imei-checkboxes input[type="checkbox"]:checked');
    const qtyDisplay = lineDiv.querySelector('.line-qty-display');
    qtyDisplay.value = checked.length;
}

function updateLineTotals() {
    let grandTotal = 0;
    document.querySelectorAll('#lines-container .sale-line').forEach(lineDiv => {
        const qty = parseInt(lineDiv.querySelector('.line-qty-display').value) || 0;
        const price = parseFloat(lineDiv.querySelector('.line-price').value) || 0;
        const total = qty * price;
        lineDiv.querySelector('.line-total-cell').textContent = total.toLocaleString('fr-FR') + ' Ar';
        grandTotal += total;
    });
    document.getElementById('grand-total').textContent = grandTotal.toLocaleString('fr-FR') + ' Ar';
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
