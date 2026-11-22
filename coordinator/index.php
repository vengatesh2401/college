<?php
require_once dirname(__DIR__) . '/includes/coord_boot.php';
$pdo = db();
$n = function (string $sql) use ($pdo, $scopeClubId) { $s = $pdo->prepare($sql); $s->execute([$scopeClubId]); return (int)$s->fetchColumn(); };
$stats = [
    ['fa-user-group', 'Members', $n('SELECT COUNT(*) FROM club_members WHERE club_id=?'), 'members'],
    ['fa-inbox', 'Pending applications', $n('SELECT COUNT(*) FROM membership_requests WHERE club_id=? AND status="pending"'), 'requests'],
    ['fa-calendar-days', 'Upcoming events', $n('SELECT COUNT(*) FROM events WHERE club_id=? AND event_date>=NOW()'), 'events'],
    ['fa-ticket', 'Event sign-ups', $n('SELECT COUNT(*) FROM event_registrations r JOIN events e ON e.id=r.event_id WHERE e.club_id=?'), 'events'],
];
$st = $pdo->prepare('SELECT e.title label, COUNT(r.id) value FROM events e LEFT JOIN event_registrations r ON r.event_id=e.id WHERE e.club_id=? GROUP BY e.id ORDER BY e.event_date DESC LIMIT 6');
$st->execute([$scopeClubId]); $evChart = array_reverse($st->fetchAll());
$st = $pdo->prepare('SELECT status label, COUNT(*) value FROM membership_requests WHERE club_id=? GROUP BY status');
$st->execute([$scopeClubId]); $reqChart = $st->fetchAll();
$pageTitle = $club['name']; $active = 'index';
include dirname(__DIR__) . '/includes/dash_header.php';
?>
<div class="stat-grid">
<?php foreach ($stats as [$icon, $label, $val, $link]): ?>
  <a class="stat-card" href="<?= e(url("coordinator/$link.php")) ?>"><span class="stat-icon"><i class="fa-solid <?= $icon ?>"></i></span><div><b data-count="<?= $val ?>">0</b><span><?= e($label) ?></span></div></a>
<?php endforeach; ?>
</div>
<div class="chart-grid">
  <section class="card"><h3>Registrations per event</h3><div class="chart" data-chart="bar" data-rows='<?= e(json_encode($evChart)) ?>'></div></section>
  <section class="card"><h3>Application status</h3><div class="chart" data-chart="donut" data-rows='<?= e(json_encode($reqChart)) ?>'></div></section>
</div>
<div class="toolbar"><a class="btn btn-primary" href="<?= e(url('coordinator/event-form.php')) ?>"><i class="fa-solid fa-plus"></i> Create event</a>
  <a class="btn btn-ghost" href="<?= e(url('club-details.php?slug=' . urlencode($club['slug']))) ?>">View public page</a></div>
<?php include dirname(__DIR__) . '/includes/dash_footer.php'; ?>
