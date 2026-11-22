<?php
/** Public / student layout header. Set $pageTitle (and optionally $bodyClass) before including. */
require_once __DIR__ . '/auth.php';
$user = current_user();
$pageTitle = $pageTitle ?? 'Find your people';
$bodyClass = $bodyClass ?? '';
$unread = $user ? unread_count((int)$user['id']) : 0;
$cur = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> · <?= e(APP_NAME) ?></title>
<meta name="description" content="Discover college clubs that match your interests, skills and career goals.">
<script>try{var t=localStorage.getItem('ccf-theme')||(matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light');document.documentElement.dataset.theme=t}catch(e){}</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700;12..96,800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
<link rel="stylesheet" href="<?= e(url('assets/css/responsive.css')) ?>">
<script>window.CCF={base:<?= json_encode(BASE_URL) ?>,csrf:<?= json_encode(csrf_token()) ?>,loggedIn:<?= $user ? 'true' : 'false' ?>};</script>
</head>
<body class="<?= e($bodyClass) ?>">
<div id="page-loader" aria-hidden="true"><div class="loader-orb"></div></div>
<header class="site-header">
  <div class="container nav-wrap">
    <a href="<?= e(url('index.php')) ?>" class="brand"><span class="brand-mark"><i class="fa-solid fa-compass"></i></span><span><?= e(APP_NAME) ?></span></a>
    <button class="nav-toggle" aria-label="Toggle menu" aria-expanded="false"><i class="fa-solid fa-bars"></i></button>
    <nav class="main-nav" id="main-nav">
      <a href="<?= e(url('index.php')) ?>" class="<?= $cur === 'index.php' ? 'active' : '' ?>">Home</a>
      <a href="<?= e(url('clubs.php')) ?>" class="<?= in_array($cur, ['clubs.php', 'club-details.php']) ? 'active' : '' ?>">Clubs</a>
      <a href="<?= e(url('events.php')) ?>" class="<?= $cur === 'events.php' ? 'active' : '' ?>">Events</a>
      <?php if ($user && $user['role'] === 'student'): ?>
        <a href="<?= e(url('clubs.php?suggested=1')) ?>"><i class="fa-solid fa-wand-magic-sparkles"></i> For you</a>
      <?php endif; ?>
      <div class="search-box" role="search">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="search" id="live-search" placeholder="Search clubs…" autocomplete="off" aria-label="Search clubs">
        <div class="search-results" id="search-results" hidden></div>
      </div>
      <div class="nav-actions">
        <button class="icon-btn" id="theme-toggle" aria-label="Toggle dark mode"><i class="fa-solid fa-moon"></i><i class="fa-solid fa-sun"></i></button>
        <?php if ($user): ?>
          <a class="icon-btn" href="<?= e(url('student/notifications.php')) ?>" aria-label="Notifications" <?= $user['role'] !== 'student' ? 'hidden' : '' ?>>
            <i class="fa-solid fa-bell"></i><?php if ($unread): ?><span class="dot"><?= $unread > 9 ? '9+' : $unread ?></span><?php endif; ?>
          </a>
          <div class="user-menu">
            <button class="user-chip" aria-haspopup="true"><span class="avatar"><?= e(strtoupper(mb_substr($user['name'], 0, 1))) ?></span><span class="uname"><?= e(explode(' ', $user['name'])[0]) ?></span><i class="fa-solid fa-chevron-down"></i></button>
            <div class="user-dropdown">
              <a href="<?= e(url(home_for($user['role']))) ?>"><i class="fa-solid fa-table-columns"></i> <?= $user['role'] === 'student' ? 'My Clubs' : 'Dashboard' ?></a>
              <?php if ($user['role'] === 'student'): ?>
              <a href="<?= e(url('student/profile.php')) ?>"><i class="fa-solid fa-user-pen"></i> Profile & Interests</a>
              <a href="<?= e(url('student/notifications.php')) ?>"><i class="fa-solid fa-bell"></i> Notifications</a>
              <?php endif; ?>
              <a href="<?= e(url('auth/logout.php')) ?>"><i class="fa-solid fa-right-from-bracket"></i> Sign out</a>
            </div>
          </div>
        <?php else: ?>
          <a class="btn btn-ghost btn-sm" href="<?= e(url('auth/login.php')) ?>">Sign in</a>
          <a class="btn btn-primary btn-sm" href="<?= e(url('auth/register.php')) ?>">Join now</a>
        <?php endif; ?>
      </div>
    </nav>
  </div>
</header>
<main class="site-main">
<div class="container"><?= flash_render() ?></div>
