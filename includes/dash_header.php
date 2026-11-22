<?php
/** Dashboard layout (admin + coordinator). Set $pageTitle and $active before including. */
require_once __DIR__ . '/auth.php';
$user = current_user();
$isAdmin = $user['role'] === 'admin';
$active = $active ?? '';
$base = $isAdmin ? 'admin' : 'coordinator';
$links = $isAdmin ? [
    ['index', 'fa-chart-pie', 'Dashboard'],
    ['clubs', 'fa-people-group', 'Clubs'],
    ['categories', 'fa-tags', 'Categories'],
    ['students', 'fa-user-graduate', 'Students'],
    ['requests', 'fa-inbox', 'Requests'],
    ['members', 'fa-id-card', 'Members'],
    ['events', 'fa-calendar-days', 'Events'],
] : [
    ['index', 'fa-chart-pie', 'Overview'],
    ['requests', 'fa-inbox', 'Applications'],
    ['members', 'fa-id-card', 'Members'],
    ['events', 'fa-calendar-days', 'Events'],
    ['club-edit', 'fa-pen-to-square', 'Club Info'],
];
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? 'Dashboard') ?> · <?= e(APP_NAME) ?> <?= $isAdmin ? 'Admin' : 'Coordinator' ?></title>
<script>try{var t=localStorage.getItem('ccf-theme')||(matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light');document.documentElement.dataset.theme=t}catch(e){}</script>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700;12..96,800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
<link rel="stylesheet" href="<?= e(url('assets/css/responsive.css')) ?>">
<script>window.CCF={base:<?= json_encode(BASE_URL) ?>,csrf:<?= json_encode(csrf_token()) ?>,loggedIn:true};</script>
</head>
<body class="dash-body">
<div id="page-loader" aria-hidden="true"><div class="loader-orb"></div></div>
<div class="dash">
  <div class="sidebar-backdrop" id="sidebar-backdrop"></div>
  <aside class="sidebar" id="sidebar">
    <a href="<?= e(url('index.php')) ?>" class="brand"><span class="brand-mark"><i class="fa-solid fa-compass"></i></span><span><?= e(APP_NAME) ?></span></a>
    <span class="role-pill"><?= $isAdmin ? 'Administrator' : 'Coordinator' ?></span>
    <nav>
      <?php foreach ($links as [$file, $icon, $label]): ?>
        <a href="<?= e(url("$base/$file.php")) ?>" class="<?= $active === $file ? 'active' : '' ?>"><i class="fa-solid <?= $icon ?>"></i><?= $label ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="sidebar-foot">
      <a href="<?= e(url('index.php')) ?>"><i class="fa-solid fa-arrow-up-right-from-square"></i> View site</a>
      <a href="<?= e(url('auth/logout.php')) ?>"><i class="fa-solid fa-right-from-bracket"></i> Sign out</a>
    </div>
  </aside>
  <div class="dash-main">
    <header class="dash-top">
      <button class="icon-btn sidebar-toggle" aria-label="Menu"><i class="fa-solid fa-bars"></i></button>
      <h1><?= e($pageTitle ?? 'Dashboard') ?></h1>
      <div class="dash-top-right">
        <button class="icon-btn" id="theme-toggle" aria-label="Toggle dark mode"><i class="fa-solid fa-moon"></i><i class="fa-solid fa-sun"></i></button>
        <span class="user-chip static"><span class="avatar"><?= e(strtoupper(mb_substr($user['name'], 0, 1))) ?></span><span class="uname"><?= e($user['name']) ?></span></span>
      </div>
    </header>
    <section class="dash-content">
      <?= flash_render() ?>
