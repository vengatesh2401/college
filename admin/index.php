<?php
require_once dirname(__DIR__) . '/includes/auth.php';
$user = require_role('admin');
$pdo = db();
$one = fn(string $sql) => (int)$pdo->query($sql)->fetchColumn();
$stats = [
    ['fa-user-graduate', 'Students', $one('SELECT COUNT(*) FROM users WHERE role="student"'), 'students'],
    ['fa-people-group', 'Active clubs', $one('SELECT COUNT(*) FROM clubs WHERE status="active"'), 'clubs'],
    ['fa-id-card', 'Memberships', $one('SELECT COUNT(*) FROM club_members'), 'members'],
    ['fa-inbox', 'Pending requests', $one('SELECT COUNT(*) FROM membership_requests WHERE status="pending"'), 'requests'],
    ['fa-calendar-days', 'Upcoming events', $one('SELECT COUNT(*) FROM events WHERE event_date>=NOW()'), 'events'],
    ['fa-ticket', 'Event sign-ups', $one('SELECT COUNT(*) FROM event_registrations'), 'events'],
];
$topClubs = $pdo->query('SELECT c.name label, COUNT(m.id) value FROM clubs c LEFT JOIN club_members m ON m.club_id=c.id GROUP BY c.id, c.name ORDER BY value DESC, c.name LIMIT 6')->fetchAll();
$reqStatus = $pdo->query('SELECT status label, COUNT(*) value FROM membership_requests GROUP BY status')->fetchAll();
$byCat = $pdo->query('SELECT cat.name label, COUNT(m.id) value FROM club_categories cat LEFT JOIN clubs c ON c.category_id=cat.id LEFT JOIN club_members m ON m.club_id=c.id GROUP BY cat.id HAVING value>0 ORDER BY value DESC')->fetchAll();
$monthly = [];
for ($i = 5; $i >= 0; $i--) $monthly[date('M', strtotime("-$i month"))] = 0;
foreach ($pdo->query('SELECT DATE_FORMAT(joined_at,"%b") m, COUNT(*) n FROM club_members WHERE joined_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY YEAR(joined_at), MONTH(joined_at)')->fetchAll() as $r) if (isset($monthly[$r['m']])) $monthly[$r['m']] = (int)$r['n'];
$monthlyRows = array_map(fn($k, $v) => ['label' => $k, 'value' => $v], array_keys($monthly), $monthly);
$recent = $pdo->query('SELECT r.id, u.name student, c.name club, r.created_at FROM membership_requests r JOIN users u ON u.id=r.user_id JOIN clubs c ON c.id=r.club_id WHERE r.status="pending" ORDER BY r.created_at DESC LIMIT 5')->fetchAll();

$pageTitle = 'Dashboard'; $active = 'index';
include dirname(__DIR__) . '/includes/dash_header.php';
?>
<div class="stat-grid">
<?php foreach ($stats as [$icon, $label, $val, $link]): ?>
  <a class="stat-card" href="<?= e(url("admin/$link.php")) ?>"><span class="stat-icon"><i class="fa-solid <?= $icon ?>"></i></span><div><b data-count="<?= $val ?>">0</b><span><?= e($label) ?></span></div></a>
<?php endforeach; ?>
</div>
<div class="chart-grid">
  <section class="card"><h3>Members per club (top 6)</h3><div class="chart" data-chart="bar" data-rows='<?= e(json_encode($topClubs)) ?>'></div></section>
  <section class="card"><h3>Request status</h3><div class="chart" data-chart="donut" data-rows='<?= e(json_encode($reqStatus)) ?>'></div></section>
  <section class="card"><h3>New memberships (6 months)</h3><div class="chart" data-chart="line" data-rows='<?= e(json_encode($monthlyRows)) ?>'></div></section>
  <section class="card"><h3>Members by category</h3><div class="chart" data-chart="donut" data-rows='<?= e(json_encode($byCat)) ?>'></div></section>
</div>
<section class="card"><div class="section-head"><h3>Latest pending requests</h3><a class="link-arrow" href="<?= e(url('admin/requests.php?status=pending')) ?>">Review all <i class="fa-solid fa-arrow-right"></i></a></div>
  <?php foreach ($recent as $r): ?><div class="row-line"><b><?= e($r['student']) ?></b><span class="muted">wants to join <?= e($r['club']) ?></span><small class="muted"><?= e(time_ago($r['created_at'])) ?></small></div><?php endforeach; if (!$recent) echo '<p class="muted">Nothing pending. 🎉</p>'; ?></section>
<?php include dirname(__DIR__) . '/includes/dash_footer.php'; ?>
