<?php
require_once __DIR__ . '/config.php';

// Einfaches Seiten-Routing via ?page=
$page      = $_GET['page'] ?? 'home';
$allowedPages = ['home', 'step1', 'step2', 'step3', 'step4'];

if (!in_array($page, $allowedPages, true)) {
    $page = 'home';
}

$viewFile = __DIR__ . '/views/' . $page . '.php';
if (!file_exists($viewFile)) {
    $page     = 'home';
    $viewFile = __DIR__ . '/views/home.php';
}

include $viewFile;
