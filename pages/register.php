<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

// Rediriger si déjà connecté
if (isLoggedIn()) {
    header('Location: /pages/dashboard.php');
    exit;
}

$errors = [];
$success = false;
$data = [
    'username' => '',
    'email' => ''
];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['username'] = trim($_POST['username'] ?? '');
    $data['email'] = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Validation
    if (empty($data['username'])) {
        $errors[] = "Le nom d'utilisateur est obligatoire.";
    } elseif (strlen($data['username']) < 3) {
        $errors[] = "Le nom d'utilisateur doit contenir au moins 3 caractères.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $data['username'])) {
        $errors[] = "Le nom d'utilisateur ne peut contenir que des lettres, chiffres et underscores.";
    }

    if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'adresse email n'est pas valide.";
    }

    if (empty($password)) {
        $errors[] = "Le mot de passe est obligatoire.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Le mot de passe doit contenir au moins 6 caractères.";
    }

    if ($password !== $password_confirm) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }

    // Vérifier si l'utilisateur existe déjà
    if (empty($errors)) {
        $existing = fetchOne(
            "SELECT id FROM users WHERE username = :username",
            ['username' => $data['username']]
        );
        if ($existing) {
            $errors[] = "Ce nom d'utilisateur est déjà utilisé.";
        }
    }

    // Créer le compte
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        execute(
            "INSERT INTO users (username, password, email) VALUES (:username, :password, :email)",
            [
                'username' => $data['username'],
                'password' => $hashedPassword,
                'email' => $data['email'] ?: null
            ]
        );

        $_SESSION['flash_message'] = 'Compte créé avec succès ! Vous pouvez maintenant vous connecter.';
        $_SESSION['flash_type'] = 'success';
        header('Location: /pages/login.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un compte - PhoneStock</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>PhoneStock</h1>
                <p>Créer un nouveau compte</p>
            </div>

            <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0" style="margin: 0; padding-left: 1.5rem;">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="username" class="form-label">Nom d'utilisateur *</label>
                    <input type="text" id="username" name="username" class="form-control"
                           value="<?= htmlspecialchars($data['username']) ?>"
                           placeholder="Choisissez un nom d'utilisateur" required autofocus>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Email (optionnel)</label>
                    <input type="email" id="email" name="email" class="form-control"
                           value="<?= htmlspecialchars($data['email']) ?>"
                           placeholder="votre@email.com">
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Mot de passe *</label>
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="Minimum 6 caractères" required>
                </div>

                <div class="form-group">
                    <label for="password_confirm" class="form-label">Confirmer le mot de passe *</label>
                    <input type="password" id="password_confirm" name="password_confirm" class="form-control"
                           placeholder="Répétez le mot de passe" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Créer mon compte
                </button>
            </form>

            <p class="text-center text-muted mt-2">
                Déjà un compte ? <a href="/pages/login.php">Se connecter</a>
            </p>
        </div>
    </div>
</body>
</html>
