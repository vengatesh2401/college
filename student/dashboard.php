<?php
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/queries.php';
require_once dirname(__DIR__) . '/includes/components.php';
$u = require_role('student');
$pdo = db();

$st = $pdo->prepare(CLUB_SELECT . ' JOIN club_members mm ON mm.club_id=c.id AND mm.user_id=? ORDER BY mm.joined_at DESC');
$st->execute([$u['id']]);
$myClubs = $st->fetchAll();

$st = $pdo->prepare('SELECT r.*, c.name, c.slug, c.logo, cat.color AS cat_color FROM membership_requests r
    JOIN clubs c ON c.id=r.club_id JOIN club_categories cat ON cat.id=c.category_id
    WHERE r.user_id=? AND r.status<>"approved" ORDER BY r.created_at DESC');
$st->execute([$u['id']]);
$requests = $st->fetchAll();

$st = $pdo->prepare('SELECT e.*, c.name AS club_name, c.slug AS club_slug, 1 AS registered,
        (SELECT COUNT(*) FROM event_registrations x WHERE x.event_id=e.id) AS reg_count
    FROM event_registrations r JOIN events e ON e.id=r.event_id JOIN clubs c ON c.id=e.club_id
    WHERE r.user_id=? AND e.event_date>=NOW() ORDER BY e.event_date');
$st->execute([$u['id']]);
$myEvents = $st->fetchAll();
$suggested = suggest_clubs((int)$u['id'], 3);

$pageTitle = 'My Clubs';
include dirname(__DIR__) . '/includes/header.php';
?>
<section class="page-head"><div class="container">
  <h1>Hi, <?= e(explode(' ', $u['name'])[0]) ?> 👋</h1>
  <p class="muted">Your clubs, requests and registered events in one place.</p>
  <div class="mini-stats">
    <div><b><?= count($myClubs) ?></b><span>Clubs joined</span></div>
    <div><b><?= count(array_filter($requests, fn($r) => $r['status'] === 'pending')) ?></b><span>Pending requests</span></div>
    <div><b><?= count($myEvents) ?></b><span>Events registered</span></div>
  </div>
</div></section>

<div class="container">
  <section class="section-sm"><div class="section-head"><h2>My clubs</h2><a class="link-arrow" href="<?= e(url('clubs.php')) ?>">Find more <i class="fa-solid fa-arrow-right"></i></a></div>
    <?php if ($myClubs): ?><div class="card-grid"><?php foreach ($myClubs as $c) echo club_card($c); ?></div>
    <?php else: echo empty_state('people-group', "You haven't joined any club yet", 'Explore clubs and send your first request.', '<a class="btn btn-primary" href="' . e(url('clubs.php')) . '">Explore clubs</a>'); endif; ?>
  </section>

  <section class="section-sm"><h2>Membership requests</h2>
    <?php if ($requests): ?><div class="table-wrap"><table class="table"><thead><tr><th>Club</th><th>Sent</th><th>Status</th><th></th></tr></thead><tbody>
      <?php foreach ($requests as $r): ?><tr>
        <td><a class="inline-club" href="<?= e(url('club-details.php?slug=' . urlencode($r['slug']))) ?>"><?= club_logo($r, 'sm') ?><b><?= e($r['name']) ?></b></a></td>
        <td><?= e(time_ago($r['created_at'])) ?></td><td><?= status_badge($r['status']) ?></td>
        <td class="right"><?php if ($r['status'] === 'pending'): ?>
          <form method="post" action="<?= e(url('student/membership.php')) ?>"><?= csrf_field() ?><input type="hidden" name="club_id" value="<?= (int)$r['club_id'] ?>"><input type="hidden" name="action" value="cancel"><input type="hidden" name="return" value="dashboard"><button class="btn btn-ghost btn-sm">Cancel</button></form>
        <?php elseif ($r['status'] === 'rejected'): ?><a class="btn btn-ghost btn-sm" href="<?= e(url('club-details.php?slug=' . urlencode($r['slug']))) ?>">Re-apply</a><?php endif; ?></td>
      </tr><?php endforeach; ?></tbody></table></div>
    <?php else: ?><p class="muted">No pending or past requests.</p><?php endif; ?>
  </section>

  <section class="section-sm"><h2>My upcoming events</h2>
    <div class="stack"><?php foreach ($myEvents as $ev) echo event_card($ev, $u); ?>
    <?php if (!$myEvents) echo empty_state('calendar-check', 'No registered events', 'Browse upcoming events and register.', '<a class="btn btn-primary" href="' . e(url('events.php')) . '">See events</a>'); ?></div>
  </section>

  <?php if ($suggested): ?><section class="section-sm"><div class="section-head"><h2>Suggested for you</h2><a class="link-arrow" href="<?= e(url('clubs.php?suggested=1')) ?>">More <i class="fa-solid fa-arrow-right"></i></a></div>
    <div class="card-grid"><?php foreach ($suggested as $c) echo club_card($c, $c['why']); ?></div></section><?php endif; ?>
</div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
