<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($title ?? 'Massenpsychologie-Simulator') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="/massenpsychologie/assets/css/style.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/d3/7.9.0/d3.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/marked/9.1.6/marked.min.js"></script>
<script src="/massenpsychologie/assets/js/app.js"></script>
</head>
<body>
<div class="app-shell">

<header class="topbar">
  <a class="topbar-brand" href="/massenpsychologie/">
    <div class="topbar-icon">&#9680;</div>
    Massenpsychologie-Simulator
  </a>
  <span class="topbar-sub">Gustave Le Bon &middot; mass_dynamics-Algorithmus</span>
  <div class="topbar-spacer"></div>
  <a href="/massenpsychologie/" class="topbar-action">&#8962; Projekte</a>
</header>

<main class="container">
