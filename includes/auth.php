<?php
/**
 * Fonctions d'authentification
 */

// Cookies sécurisés
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', 1);

session_start();

// Headers de sécurité
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(self), microphone=()');

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
        $_SESSION['last_activity'] = time();
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
 * Vérifie aussi le timeout de session (30 minutes d'inactivité)
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: /pages/login.php');
        exit;
    }

    // Timeout de session : 30 minutes
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        logout();
        header('Location: /pages/login.php?timeout=1');
        exit;
    }

    $_SESSION['last_activity'] = time();
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

// =====================================================
// Rate limiting login
// =====================================================

/**
 * Vérifie si une IP est bloquée par rate limiting (>= 5 tentatives en 15 min)
 * @param string $ip Adresse IP
 * @return bool
 */
function isRateLimited(string $ip): bool {
    require_once __DIR__ . '/../config/database.php';

    $row = fetchOne(
        "SELECT COUNT(*) as attempts FROM login_attempts WHERE ip = :ip AND attempted_at > NOW() - INTERVAL '15 minutes'",
        ['ip' => $ip]
    );

    return $row && (int)$row['attempts'] >= 5;
}

/**
 * Enregistre une tentative de login échouée
 * @param string $ip Adresse IP
 * @param string $username Nom d'utilisateur tenté
 */
function recordFailedLogin(string $ip, string $username): void {
    require_once __DIR__ . '/../config/database.php';

    execute(
        "INSERT INTO login_attempts (ip, username) VALUES (:ip, :username)",
        ['ip' => $ip, 'username' => $username]
    );
}

/**
 * Efface les tentatives de login pour une IP (après login réussi)
 * @param string $ip Adresse IP
 */
function clearLoginAttempts(string $ip): void {
    require_once __DIR__ . '/../config/database.php';

    execute("DELETE FROM login_attempts WHERE ip = :ip", ['ip' => $ip]);
}
