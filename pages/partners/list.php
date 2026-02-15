<?php
$pageTitle = 'Partenaires';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireLogin();

$userId = $_SESSION['user_id'];

// Partenariats acceptés
$partners = fetchAll(
    "SELECT p.*,
            CASE WHEN p.requester_id = :uid THEN u2.username ELSE u1.username END as partner_name,
            CASE WHEN p.requester_id = :uid2 THEN p.receiver_id ELSE p.requester_id END as partner_id
     FROM partnerships p
     LEFT JOIN users u1 ON p.requester_id = u1.id
     LEFT JOIN users u2 ON p.receiver_id = u2.id
     WHERE (p.requester_id = :uid3 OR p.receiver_id = :uid4) AND p.status = 'accepted'
     ORDER BY p.updated_at DESC",
    ['uid' => $userId, 'uid2' => $userId, 'uid3' => $userId, 'uid4' => $userId]
);

// Demandes reçues en attente
$pendingReceived = fetchAll(
    "SELECT p.*, u.username as requester_name
     FROM partnerships p
     LEFT JOIN users u ON p.requester_id = u.id
     WHERE p.receiver_id = :uid AND p.status = 'pending'
     ORDER BY p.created_at DESC",
    ['uid' => $userId]
);

// Demandes envoyées en attente
$pendingSent = fetchAll(
    "SELECT p.*, u.username as receiver_name
     FROM partnerships p
     LEFT JOIN users u ON p.receiver_id = u.id
     WHERE p.requester_id = :uid AND p.status = 'pending'
     ORDER BY p.created_at DESC",
    ['uid' => $userId]
);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title">Mes partenaires</h1>
        <a href="/pages/partners/invite.php" class="btn btn-primary">+ Inviter un partenaire</a>
    </div>

    <?php if (empty($partners)): ?>
        <p class="text-muted text-center">Aucun partenaire pour le moment. Invitez quelqu'un pour partager vos stocks.</p>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Partenaire</th>
                        <th>Depuis</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($partners as $partner): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($partner['partner_name']) ?></strong></td>
                            <td><?= date('d/m/Y', strtotime($partner['updated_at'])) ?></td>
                            <td>
                                <a href="/pages/partners/remove.php?id=<?= $partner['id'] ?>"
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Supprimer ce partenariat ? Vous ne verrez plus le stock de ce partenaire.')">
                                    Supprimer
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($pendingReceived)): ?>
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Demandes reçues</h2>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>De</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pendingReceived as $request): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($request['requester_name']) ?></strong></td>
                        <td><?= date('d/m/Y H:i', strtotime($request['created_at'])) ?></td>
                        <td>
                            <div class="btn-group">
                                <a href="/pages/partners/respond.php?id=<?= $request['id'] ?>&action=accept"
                                   class="btn btn-sm btn-success">Accepter</a>
                                <a href="/pages/partners/respond.php?id=<?= $request['id'] ?>&action=reject"
                                   class="btn btn-sm btn-danger">Refuser</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($pendingSent)): ?>
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Demandes envoyées</h2>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>A</th>
                    <th>Date</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pendingSent as $request): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($request['receiver_name']) ?></strong></td>
                        <td><?= date('d/m/Y H:i', strtotime($request['created_at'])) ?></td>
                        <td><span class="badge badge-warning">En attente</span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
