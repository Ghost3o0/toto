<?php
$pageTitle = 'Inviter un partenaire';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireLogin();

$userId = $_SESSION['user_id'];
$errors = [];
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de sécurité invalide.';
    } else {
        $username = trim($_POST['username'] ?? '');

        if (empty($username)) {
            $errors[] = "Le nom d'utilisateur est obligatoire.";
        } else {
            // Vérifier que l'utilisateur existe
            $targetUser = fetchOne("SELECT id, username FROM users WHERE username = :username", ['username' => $username]);

            if (!$targetUser) {
                $errors[] = "Utilisateur '$username' introuvable.";
            } elseif ($targetUser['id'] == $userId) {
                $errors[] = "Vous ne pouvez pas vous inviter vous-même.";
            } else {
                // Vérifier qu'il n'y a pas déjà un partenariat ou une demande
                $existing = fetchOne(
                    "SELECT id, status FROM partnerships
                     WHERE (requester_id = :uid AND receiver_id = :tid)
                        OR (requester_id = :tid2 AND receiver_id = :uid2)",
                    ['uid' => $userId, 'tid' => $targetUser['id'], 'tid2' => $targetUser['id'], 'uid2' => $userId]
                );

                if ($existing) {
                    if ($existing['status'] === 'accepted') {
                        $errors[] = "Vous êtes déjà partenaire avec $username.";
                    } elseif ($existing['status'] === 'pending') {
                        $errors[] = "Une demande de partenariat est déjà en cours avec $username.";
                    } else {
                        // Rejeté précédemment, on peut renvoyer
                        execute(
                            "UPDATE partnerships SET requester_id = :uid, receiver_id = :tid, status = 'pending', updated_at = CURRENT_TIMESTAMP WHERE id = :id",
                            ['uid' => $userId, 'tid' => $targetUser['id'], 'id' => $existing['id']]
                        );
                        $_SESSION['flash_message'] = "Demande de partenariat envoyée à $username.";
                        $_SESSION['flash_type'] = 'success';
                        header('Location: /pages/partners/list.php');
                        exit;
                    }
                } else {
                    execute(
                        "INSERT INTO partnerships (requester_id, receiver_id) VALUES (:uid, :tid)",
                        ['uid' => $userId, 'tid' => $targetUser['id']]
                    );
                    $_SESSION['flash_message'] = "Demande de partenariat envoyée à $username.";
                    $_SESSION['flash_type'] = 'success';
                    header('Location: /pages/partners/list.php');
                    exit;
                }
            }
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title">Inviter un partenaire</h1>
        <a href="/pages/partners/list.php" class="btn btn-outline">Retour</a>
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

    <div class="alert alert-info">
        En devenant partenaires, vous pourrez voir et vendre mutuellement vos stocks de téléphones.
    </div>

    <form method="POST" action="">
        <?php csrfField(); ?>

        <div class="form-group">
            <label for="username" class="form-label">Nom d'utilisateur du partenaire *</label>
            <input type="text" id="username" name="username" class="form-control"
                   value="<?= htmlspecialchars($username) ?>"
                   placeholder="Saisir le nom d'utilisateur" required>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn btn-primary">Envoyer l'invitation</button>
            <a href="/pages/partners/list.php" class="btn btn-outline">Annuler</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
