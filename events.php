<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/queries.php';
require_once __DIR__ . '/includes/components.php';

$user = current_user();
$cats = all_categories();
$f = ['q' => mb_substr(input('q'), 0, 80), 'cat' => input_int('cat')];
$events = upcoming_events($user['id'] ?? null, $f, 60);

// group by month
$groups = [];
foreach ($events as $ev) $groups[date('F Y', strtotime($ev['event_date']))][] = $ev;

$pageTitle = 'Upcoming events';
include __DIR__ . '/includes/header.php';
?>
<section class="page-head"><div class="container">
  <h1>Upcoming events</h1><p class="muted">Workshops, contests, showcases and more. Register in one tap.</p>
  <form class="inline-filter" method="get">
    <input type="search" name="q" value="<?= e($f['q']) ?>" placeholder="Search events">
    <select name="cat"><option value="0">All categories</option><?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $f['cat'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select>
    <button class="btn btn-primary">Filter</button>
  </form>
</div></section>
<div class="container narrow">
  <?php foreach ($groups as $month => $list): ?>
    <h2 class="month-head"><?= e($month) ?></h2>
    <div class="stack"><?php foreach ($list as $ev) echo event_card($ev, $user); ?></div>
  <?php endforeach; ?>
  <?php if (!$groups) echo empty_state('calendar-xmark', 'No events found', 'Try a different search or category.'); ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
