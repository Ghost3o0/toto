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
    <meta name="theme-color" content="#2563eb">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="PhoneStock">
    <title><?= $pageTitle ?? 'Gestion de Stock' ?> - PhoneStock</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/assets/icons/icon-192.png">
</head>
<body>
    <?php if (isLoggedIn()): ?>
    <nav class="navbar">
        <div class="navbar-brand">
            <a href="/pages/dashboard.php">PhoneStock</a>
        </div>
        <button class="navbar-toggle" aria-label="Ouvrir le menu" aria-expanded="false"> 
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
        </button>
        <ul class="navbar-menu">
            <li class="<?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                <a href="/pages/dashboard.php">Tableau de bord</a>
            </li>
            <li class="<?= in_array($currentPage, ['list', 'add', 'edit']) && strpos($_SERVER['PHP_SELF'], '/phones/') !== false ? 'active' : '' ?>">
                <a href="/pages/phones/list.php">Téléphones</a>
            </li>
            <li class="<?= in_array($currentPage, ['movements', 'adjust']) ? 'active' : '' ?>">
                <a href="/pages/stock/movements.php">Stock</a>
            </li>
            <li class="<?= in_array($currentPage, ['list', 'create', 'view']) && strpos($_SERVER['PHP_SELF'], '/sales/') !== false ? 'active' : '' ?>">
                <a href="/pages/sales/list.php">Ventes</a>
            </li>
            <li class="<?= strpos($_SERVER['PHP_SELF'], '/partners/') !== false ? 'active' : '' ?>">
                <a href="/pages/partners/list.php">Partenaires</a>
            </li>
            <li>
                <a href="/pages/logout.php">Déconnexion</a>
            </li>
            <li>
                <a href="javascript:void(0)" onclick="toggleTheme()" title="Changer le thème">
                    <svg class="sun-icon" width="18" height="18" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <svg class="moon-icon" width="18" height="18" style="display:none;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                </a>
            </li>
        </ul>
        <div class="navbar-user">
            <span>Bonjour, <?= htmlspecialchars($currentUser['username']) ?></span>
        </div>
    </nav>
    <?php endif; ?>

    <script>
    // Toggle main navbar on mobile
    (function(){
        function qs(sel, ctx){ return (ctx||document).querySelector(sel); }
        document.addEventListener('DOMContentLoaded', function(){
            var toggle = qs('.navbar-toggle');
            var menu = qs('.navbar-menu');
            if(toggle && menu){
                toggle.addEventListener('click', function(){
                    var expanded = this.getAttribute('aria-expanded') === 'true';
                    this.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                    menu.classList.toggle('active');
                });
            }

            // Convert .btn-group in card headers into hamburger toggles on small screens
            function updateActionToggles(){
                var headers = document.querySelectorAll('.card .card-header');
                headers.forEach(function(h){
                    if(h.querySelector('.action-hamburger')) return; // already added
                    var btnGroup = h.querySelector('.btn-group');
                    if(!btnGroup) return;
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'action-hamburger';
                    btn.setAttribute('aria-expanded','false');
                    btn.title = 'Actions';
                    btn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>';
                    btn.addEventListener('click', function(e){
                        var expanded = this.getAttribute('aria-expanded') === 'true';
                        this.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                        btnGroup.classList.toggle('visible-mobile');
                    });
                    h.appendChild(btn);
                });
            }
            updateActionToggles();
            window.addEventListener('resize', updateActionToggles);
        });
    })();
    </script>

    <main class="container">
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'info' ?>">
                <?= htmlspecialchars($_SESSION['flash_message']) ?>
            </div>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?>
