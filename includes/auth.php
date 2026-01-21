<?php
/**
 * Fonctions d'authentification
 */

session_start();

/**
 * Vérifie si l'utilisateur est connecté
 * @return bool
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Récupère l'utilisateur connecté
 * @return array|null
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email' => $_SESSION['email'] ?? ''
    ];
}

/**
 * Connecte un utilisateur
 * @param string $username Nom d'utilisateur
 * @param string $password Mot de passe
 * @return bool Succès de la connexion
 */
function login(string $username, string $password): bool {
    require_once __DIR__ . '/../config/database.php';

    $user = fetchOne(
        "SELECT id, username, password, email FROM users WHERE username = :username",
        ['username' => $username]
    );

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        return true;
    }

    return false;
}

/**
 * Déconnecte l'utilisateur
 */
function logout(): void {
    $_SESSION = [];
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    session_destroy();
}

/**
 * Protège une page - redirige vers login si non connecté
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: /pages/login.php');
        exit;
    }
}

/**
 * Génère un token CSRF
 * @return string
 */
function generateCsrfToken(): string {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie un token CSRF
 * @param string $token Token à vérifier
 * @return bool
 */
function verifyCsrfToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Affiche un champ caché CSRF dans un formulaire
 */
function csrfField(): void {
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCsrfToken()) . '">';
}
