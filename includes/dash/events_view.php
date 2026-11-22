<?php
/** Event list + registrants. Expects $user, $scopeClubId, $base. */
$pdo = db();
if (is_post()) {
    csrf_check();
    $eid = input_int('event_id');
    $s = $pdo->prepare('SELECT club_id FROM events WHERE id=?'); $s->execute([$eid]);
    $cid = $s->fetchColumn();
    if (!$cid || ($scopeClubId !== null && (int)$cid !== $scopeClubId)) { http_response_code(403); exit('Not allowed.'); }
    $pdo->prepare('DELETE FROM events WHERE id=?')->execute([$eid]);
    flash('success', 'Event deleted.');
    redirect($base . '/events.php');
}
$where = $scopeClubId !== null ? 'WHERE e.club_id=' . (int)$scopeClubId : '';
$rows = $pdo->query('SELECT e.*, c.name AS club_name, (SELECT COUNT(*) FROM event_registrations r WHERE r.event_id=e.id) AS regs FROM events e JOIN clubs c ON c.id=e.club_id ' . $where . ' ORDER BY e.event_date DESC LIMIT 200')->fetchAll();

$viewId = input_int('registrants');
$regs = [];
if ($viewId) {
    $s = $pdo->prepare('SELECT u.name,u.email,sp.department,r.created_at FROM event_registrations r JOIN users u ON u.id=r.user_id LEFT JOIN student_profiles sp ON sp.user_id=u.id JOIN events e ON e.id=r.event_id WHERE r.event_id=?' . ($scopeClubId !== null ? ' AND e.club_id=' . (int)$scopeClubId : '') . ' ORDER BY r.created_at');
    $s->execute([$viewId]);
    $regs = $s->fetchAll();
}
?>
<div class="toolbar"><a class="btn btn-primary" href="<?= e(url($base . '/event-form.php')) ?>"><i class="fa-solid fa-plus"></i> New event</a></div>
<?php if ($viewId): ?><div class="card"><h3>Registrants (<?= count($regs) ?>)</h3>
  <?php foreach ($regs as $r): ?><div class="row-line"><b><?= e($r['name']) ?></b><span class="muted"><?= e($r['email']) ?> · <?= e($r['department'] ?: '—') ?></span></div><?php endforeach; if (!$regs) echo '<p class="muted">No registrations yet.</p>'; ?>
  <a class="btn btn-ghost btn-sm" href="?">Close</a></div><?php endif; ?>
<div class="table-wrap"><table class="table"><thead><tr><th>Event</th><?php if ($scopeClubId === null): ?><th>Club</th><?php endif; ?><th>When</th><th>Venue</th><th>Registered</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): $past = strtotime($r['event_date']) < time(); ?><tr class="<?= $past ? 'is-past' : '' ?>">
  <td><b><?= e($r['title']) ?></b><?= $past ? ' <span class="badge badge-rejected">Past</span>' : '' ?></td>
  <?php if ($scopeClubId === null): ?><td><?= e($r['club_name']) ?></td><?php endif; ?>
  <td><?= e(fmt_date($r['event_date'])) ?><br><small class="muted"><?= e(fmt_time($r['event_date'])) ?></small></td><td><?= e($r['venue'] ?: '—') ?></td>
  <td><a href="?registrants=<?= (int)$r['id'] ?>"><?= (int)$r['regs'] ?><?= $r['capacity'] ? ' / ' . (int)$r['capacity'] : '' ?></a></td>
  <td class="right nowrap"><a class="btn btn-ghost btn-sm" href="<?= e(url($base . '/event-form.php?id=' . (int)$r['id'])) ?>"><i class="fa-solid fa-pen"></i></a>
    <form method="post" class="inline" data-confirm="Delete this event and its registrations?"><?= csrf_field() ?><input type="hidden" name="event_id" value="<?= (int)$r['id'] ?>"><button class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button></form></td>
</tr><?php endforeach; if (!$rows): ?><tr><td colspan="6" class="center muted">No events yet.</td></tr><?php endif; ?></tbody></table></div>
