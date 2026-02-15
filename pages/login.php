<?php
require_once __DIR__ . '/../includes/auth.php';

// Rediriger si déjà connecté
if (isLoggedIn()) {
    header('Location: /pages/dashboard.php');
    exit;
}

$error = '';

// Message de timeout de session
if (isset($_GET['timeout'])) {
    $error = 'Votre session a expiré. Veuillez vous reconnecter.';
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'];

    if (empty($username) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } elseif (isRateLimited($ip)) {
        $error = 'Trop de tentatives. Veuillez réessayer dans 15 minutes.';
    } elseif (login($username, $password)) {
        clearLoginAttempts($ip);
        header('Location: /pages/dashboard.php');
        exit;
    } else {
        recordFailedLogin($ip, $username);
        $error = 'Identifiants incorrects.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Mystate</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>Mystate</h1>
                <p>Connectez-vous pour accéder à la gestion de stock</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="username" class="form-label">Nom d'utilisateur</label>
                    <input type="text" id="username" name="username" class="form-control"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           placeholder="Entrez votre nom d'utilisateur" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Mot de passe</label>
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="Entrez votre mot de passe" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Se connecter
                </button>
            </form>

            <p class="text-center text-muted mt-2">
                Pas encore de compte ? <a href="/pages/register.php">Créer un compte</a>
            </p>
        </div>
    </div>
</body>
</html>
