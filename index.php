<?php
/**
 * Point d'entrée de l'application PhoneStock
 * Redirige vers la page appropriée
 */

require_once __DIR__ . '/includes/auth.php';

// Rediriger vers le dashboard si connecté, sinon vers login
if (isLoggedIn()) {
    header('Location: /pages/dashboard.php');
} else {
    header('Location: /pages/login.php');
}
exit;
