<?php
require_once __DIR__ . '/../includes/auth.php';

logout();

$_SESSION['flash_message'] = 'Vous avez été déconnecté avec succès.';
$_SESSION['flash_type'] = 'info';

header('Location: /pages/login.php');
exit;
