<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/queries.php';
require_once __DIR__ . '/includes/components.php';

$pdo = db();
$stats = [
    'clubs'   => (int)$pdo->query('SELECT COUNT(*) FROM clubs WHERE status="active"')->fetchColumn(),
    'members' => (int)$pdo->query('SELECT COUNT(*) FROM club_members')->fetchColumn(),
    'events'  => (int)$pdo->query('SELECT COUNT(*) FROM events WHERE event_date>=NOW()')->fetchColumn(),
];
$user = current_user();
$cats = all_categories();
$popular = popular_clubs(6);
$recent = recent_clubs(3);
$events = upcoming_events($user['id'] ?? null, [], 3);
$suggested = ($user && $user['role'] === 'student') ? suggest_clubs((int)$user['id'], 3) : [];

$pageTitle = 'Find your people';
include __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <div class="container hero-grid">
    <div class="hero-copy">
      <span class="eyebrow"><i class="fa-solid fa-sparkles"></i> <?= (int)$stats['clubs'] ?> clubs · <?= (int)$stats['events'] ?> upcoming events</span>
      <h1>Find the club that <em>feels like home</em>.</h1>
      <p class="lead">Discover clubs by interest, skill and career goal. Request to join in a tap, and never miss an event again.</p>
      <form class="hero-search" action="<?= e(url('clubs.php')) ?>" method="get">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="search" name="q" placeholder="Try “robotics”, “Python”, “photography”…" aria-label="Search clubs">
        <button class="btn btn-primary" type="submit">Find clubs</button>
      </form>
      <div class="hero-stats">
        <div><b data-count="<?= $stats['clubs'] ?>">0</b><span>Active clubs</span></div>
        <div><b data-count="<?= $stats['members'] ?>">0</b><span>Memberships</span></div>
        <div><b data-count="<?= $stats['events'] ?>">0</b><span>Upcoming events</span></div>
      </div>
    </div>
    <div class="hero-art" aria-hidden="true">
      <div class="blob b1"></div><div class="blob b2"></div>
      <div class="float-card fc1"><i class="fa-solid fa-code"></i><b>CodeCraft</b><small>Wed · Lab 3</small></div>
      <div class="float-card fc2"><i class="fa-solid fa-camera-retro"></i><b>Photo Walk</b><small>Sat · 8 AM</small></div>
      <div class="float-card fc3"><i class="fa-solid fa-music"></i><b>Open Mic</b><small>This week</small></div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head"><div><h2>Pick what you're into</h2><p class="muted">Choose one or more interests to jump straight to matching clubs.</p></div></div>
    <form action="<?= e(url('clubs.php')) ?>" method="get" class="interest-picker">
      <?php foreach ($cats as $c): ?>
        <label class="interest" style="--lc:<?= e($c['color']) ?>"><input type="checkbox" name="cat[]" value="<?= (int)$c['id'] ?>"><span><i class="fa-solid <?= e($c['icon']) ?>"></i> <?= e($c['name']) ?></span></label>
      <?php endforeach; ?>
      <button class="btn btn-primary" type="submit"><i class="fa-solid fa-wand-magic-sparkles"></i> Show matching clubs</button>
    </form>
  </div>
</section>

<?php if ($suggested): ?>
<section class="section tint">
  <div class="container">
    <div class="section-head"><div><h2>Picked for you</h2><p class="muted">Based on your interests and skills.</p></div><a class="link-arrow" href="<?= e(url('clubs.php?suggested=1')) ?>">See all <i class="fa-solid fa-arrow-right"></i></a></div>
    <div class="card-grid"><?php foreach ($suggested as $c) echo club_card($c, $c['why']); ?></div>
  </div>
</section>
<?php endif; ?>

<section class="section">
  <div class="container">
    <div class="section-head"><div><h2>Popular clubs</h2><p class="muted">Where most students are hanging out.</p></div><a class="link-arrow" href="<?= e(url('clubs.php?sort=popular')) ?>">Browse all <i class="fa-solid fa-arrow-right"></i></a></div>
    <div class="card-grid"><?php foreach ($popular as $c) echo club_card($c); ?></div>
  </div>
</section>

<section class="section tint">
  <div class="container two-col">
    <div>
      <div class="section-head"><div><h2>Upcoming events</h2></div><a class="link-arrow" href="<?= e(url('events.php')) ?>">All events <i class="fa-solid fa-arrow-right"></i></a></div>
      <div class="stack"><?php foreach ($events as $ev) echo event_card($ev, $user); ?><?php if (!$events) echo empty_state('calendar-xmark', 'No upcoming events yet'); ?></div>
    </div>
    <div>
      <div class="section-head"><div><h2>Just added</h2></div></div>
      <div class="stack"><?php foreach ($recent as $c): ?>
        <a class="mini-club" href="<?= e(url('club-details.php?slug=' . urlencode($c['slug']))) ?>"><?= club_logo($c, 'sm') ?><div><b><?= e($c['name']) ?></b><small class="muted"><?= e($c['cat_name']) ?> · <?= e(time_ago($c['created_at'])) ?></small></div><i class="fa-solid fa-chevron-right"></i></a>
      <?php endforeach; ?></div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head"><div><h2>How it works</h2></div></div>
    <div class="steps">
      <div class="step"><span>1</span><h3>Tell us your interests</h3><p class="muted">Pick categories and skills in your profile.</p></div>
      <div class="step"><span>2</span><h3>Discover your matches</h3><p class="muted">Filter by category, skill or department, or get suggestions.</p></div>
      <div class="step"><span>3</span><h3>Request to join</h3><p class="muted">Send a membership request and track its status.</p></div>
      <div class="step"><span>4</span><h3>Show up</h3><p class="muted">Register for events and be part of the action.</p></div>
    </div>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
