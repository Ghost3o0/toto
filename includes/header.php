<?php
require_once __DIR__ . '/auth.php';
$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Gestion de Stock' ?> - PhoneStock</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <?php if (isLoggedIn()): ?>
    <nav class="navbar">
        <div class="navbar-brand">
            <a href="/pages/dashboard.php">PhoneStock</a>
        </div>
        <ul class="navbar-menu">
            <li class="<?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                <a href="/pages/dashboard.php">Tableau de bord</a>
            </li>
            <li class="<?= in_array($currentPage, ['list', 'add', 'edit']) ? 'active' : '' ?>">
                <a href="/pages/phones/list.php">Téléphones</a>
            </li>
            <li class="<?= in_array($currentPage, ['movements', 'adjust']) ? 'active' : '' ?>">
                <a href="/pages/stock/movements.php">Stock</a>
            </li>
        </ul>
        <div class="navbar-user">
            <span>Bonjour, <?= htmlspecialchars($currentUser['username']) ?></span>
            <button class="theme-toggle" onclick="toggleTheme()" title="Changer le thème">
                <svg class="sun-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <svg class="moon-icon" style="display:none;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                </svg>
            </button>
            <a href="/pages/logout.php" class="btn btn-sm btn-outline">Déconnexion</a>
        </div>
    </nav>
    <?php endif; ?>

    <main class="container">
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'info' ?>">
                <?= htmlspecialchars($_SESSION['flash_message']) ?>
            </div>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?>
