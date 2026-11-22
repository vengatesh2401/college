<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/queries.php';
require_once __DIR__ . '/includes/components.php';

$club = club_by_slug(input('slug'));
if (!$club) { http_response_code(404); $pageTitle = 'Club not found'; include __DIR__ . '/includes/header.php';
    echo '<div class="container">' . empty_state('circle-question', 'Club not found', 'It may have been removed.', '<a class="btn btn-primary" href="' . e(url('clubs.php')) . '">Browse clubs</a>') . '</div>';
    include __DIR__ . '/includes/footer.php'; exit; }

$user = current_user();
$state = ($user && $user['role'] === 'student') ? membership_state((int)$user['id'], (int)$club['id']) : null;
$events = upcoming_events($user['id'] ?? null, ['club_id' => $club['id']], 10);
$related = array_values(array_filter(find_clubs(['cats' => [$club['category_id']]], 4, 0)[0], fn($r) => $r['id'] !== $club['id']));
$activities = array_values(array_filter(array_map('trim', explode("\n", (string)$club['activities']))));

$pageTitle = $club['name'];
include __DIR__ . '/includes/header.php';
?>
<section class="club-hero" style="--lc:<?= e($club['cat_color']) ?>">
  <div class="container club-hero-inner">
    <?= club_logo($club, 'xl') ?>
    <div>
      <a class="chip chip-cat" href="<?= e(url('clubs.php?cat[]=' . (int)$club['category_id'])) ?>"><i class="fa-solid <?= e($club['cat_icon']) ?>"></i> <?= e($club['cat_name']) ?></a>
      <h1><?= e($club['name']) ?></h1>
      <p class="lead"><?= e($club['tagline']) ?></p>
      <div class="meta"><span><i class="fa-solid fa-user-group"></i> <?= (int)$club['member_count'] ?> members</span>
        <?php if ($club['meeting_info']): ?><span><i class="fa-regular fa-clock"></i> <?= e($club['meeting_info']) ?></span><?php endif; ?>
        <span><i class="fa-solid fa-building-columns"></i> <?= e($club['department'] ?: 'All departments') ?></span></div>
    </div>
    <div class="join-box card">
      <?php if (!$user): ?>
        <h3>Want to join?</h3><p class="muted small">Sign in to send a membership request.</p>
        <a class="btn btn-primary btn-block" href="<?= e(url('auth/login.php')) ?>">Sign in to join</a>
      <?php elseif ($state === null): ?>
        <p class="muted small">Staff accounts can't join clubs.</p>
      <?php elseif ($state === 'member'): ?>
        <div class="state ok"><i class="fa-solid fa-circle-check"></i> You're a member</div>
        <form method="post" action="<?= e(url('student/membership.php')) ?>" data-confirm="Leave <?= e($club['name']) ?>?">
          <?= csrf_field() ?><input type="hidden" name="club_id" value="<?= (int)$club['id'] ?>"><input type="hidden" name="action" value="leave">
          <button class="btn btn-ghost btn-block btn-sm">Leave club</button></form>
      <?php elseif ($state === 'pending'): ?>
        <div class="state wait"><i class="fa-solid fa-hourglass-half"></i> Request pending</div>
        <form method="post" action="<?= e(url('student/membership.php')) ?>">
          <?= csrf_field() ?><input type="hidden" name="club_id" value="<?= (int)$club['id'] ?>"><input type="hidden" name="action" value="cancel">
          <button class="btn btn-ghost btn-block btn-sm">Cancel request</button></form>
      <?php else: ?>
        <form method="post" action="<?= e(url('student/membership.php')) ?>">
          <?= csrf_field() ?><input type="hidden" name="club_id" value="<?= (int)$club['id'] ?>"><input type="hidden" name="action" value="join">
          <?php if ($state === 'rejected'): ?><div class="state no"><i class="fa-solid fa-circle-xmark"></i> Previous request rejected</div><?php endif; ?>
          <label class="field"><span>Why do you want to join?</span><textarea name="message" rows="3" maxlength="500" placeholder="Optional note to the coordinator"></textarea></label>
          <button class="btn btn-primary btn-block" type="submit"><i class="fa-solid fa-paper-plane"></i> <?= $state === 'rejected' ? 'Re-apply' : 'Join club' ?></button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</section>

<div class="container detail-grid">
  <div class="detail-main">
    <section class="card"><h2>About</h2><p class="prose"><?= nl2br(e($club['description'])) ?></p>
      <?php if ($club['skills']): ?><h4>Skills you'll use or build</h4><div class="tags"><?php foreach (tags_array($club['skills']) as $t): ?><a class="tag" href="<?= e(url('clubs.php?skill=' . urlencode($t))) ?>"><?= e($t) ?></a><?php endforeach; ?></div><?php endif; ?></section>
    <?php if ($activities): ?><section class="card"><h2>Activities</h2><ul class="check-list"><?php foreach ($activities as $a): ?><li><i class="fa-solid fa-circle-check"></i> <?= e($a) ?></li><?php endforeach; ?></ul></section><?php endif; ?>
    <?php if ($club['requirements']): ?><section class="card"><h2>Membership requirements</h2><p class="prose"><?= nl2br(e($club['requirements'])) ?></p></section><?php endif; ?>
    <section><h2 class="h-gap">Upcoming events</h2>
      <div class="stack"><?php foreach ($events as $ev) echo event_card($ev, $user); ?>
      <?php if (!$events) echo empty_state('calendar-xmark', 'No upcoming events', 'Check back soon.'); ?></div></section>
  </div>
  <aside class="detail-side">
    <section class="card"><h3>Coordinators</h3>
      <dl class="info"><dt>Faculty coordinator</dt><dd><?= e($club['faculty_coordinator'] ?: '—') ?></dd><dt>Student coordinator</dt><dd><?= e($club['student_coordinator'] ?: '—') ?></dd></dl></section>
    <section class="card"><h3>Contact</h3>
      <dl class="info"><?php if ($club['contact_email']): ?><dt>Email</dt><dd><a href="mailto:<?= e($club['contact_email']) ?>"><?= e($club['contact_email']) ?></a></dd><?php endif; ?>
      <?php if ($club['contact_phone']): ?><dt>Phone</dt><dd><?= e($club['contact_phone']) ?></dd><?php endif; ?>
      <?php if ($club['meeting_info']): ?><dt>Meets</dt><dd><?= e($club['meeting_info']) ?></dd><?php endif; ?></dl></section>
    <?php if ($related): ?><section class="card"><h3>Similar clubs</h3><div class="stack tight"><?php foreach (array_slice($related, 0, 3) as $c): ?>
      <a class="mini-club" href="<?= e(url('club-details.php?slug=' . urlencode($c['slug']))) ?>"><?= club_logo($c, 'sm') ?><div><b><?= e($c['name']) ?></b><small class="muted"><?= (int)$c['member_count'] ?> members</small></div></a><?php endforeach; ?></div></section><?php endif; ?>
  </aside>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
