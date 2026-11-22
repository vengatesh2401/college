<?php
/** Membership requests. Expects $user, $scopeClubId (int|null), $base ('admin'|'coordinator'), $canApprove (bool). */
$pdo = db();
if (is_post()) {
    csrf_check();
    $rid = input_int('request_id'); $act = input('action');
    $chk = $pdo->prepare('SELECT club_id FROM membership_requests WHERE id=?'); $chk->execute([$rid]);
    $cid = $chk->fetchColumn();
    if (!$cid || ($scopeClubId !== null && (int)$cid !== $scopeClubId)) { http_response_code(403); exit('Not allowed.'); }
    if (!$canApprove) { http_response_code(403); exit('You are not authorised to approve or reject requests for this club.'); }
    if (in_array($act, ['approved', 'rejected'], true) && process_request($rid, $act, (int)$user['id'])) flash('success', 'Request ' . $act . '.');
    else flash('error', 'Request was already processed or is invalid.');
    redirect($base . '/requests.php?' . http_build_query(['status' => input('status')]));
}
$status = in_array(input('status'), ['pending', 'approved', 'rejected'], true) ? input('status') : '';
$where = []; $p = [];
if ($scopeClubId !== null) { $where[] = 'r.club_id=?'; $p[] = $scopeClubId; }
if ($status) { $where[] = 'r.status=?'; $p[] = $status; }
$st = $pdo->prepare('SELECT r.*, u.name AS student, u.email, sp.department, sp.skills, c.name AS club_name FROM membership_requests r
    JOIN users u ON u.id=r.user_id LEFT JOIN student_profiles sp ON sp.user_id=u.id JOIN clubs c ON c.id=r.club_id'
    . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY FIELD(r.status,"pending","approved","rejected"), r.created_at DESC LIMIT 200');
$st->execute($p);
$rows = $st->fetchAll();
?>
<div class="tabs"><?php foreach (['' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $k => $l): ?><a class="<?= $status === $k ? 'active' : '' ?>" href="?status=<?= $k ?>"><?= $l ?></a><?php endforeach; ?></div>
<?php if (!$canApprove): ?><div class="alert alert-info"><i class="fa-solid fa-circle-info"></i><span>Approving requests is handled by the administrator for your club. You can view applications only.</span></div><?php endif; ?>
<div class="table-wrap"><table class="table"><thead><tr><th>Student</th><?php if ($scopeClubId === null): ?><th>Club</th><?php endif; ?><th>Department</th><th>Message</th><th>Date</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr>
  <td><b><?= e($r['student']) ?></b><br><small class="muted"><?= e($r['email']) ?></small></td>
  <?php if ($scopeClubId === null): ?><td><?= e($r['club_name']) ?></td><?php endif; ?>
  <td><?= e($r['department'] ?: '—') ?></td><td class="wrap"><?= e($r['message'] ?: '—') ?></td><td><?= e(time_ago($r['created_at'])) ?></td><td><?= status_badge($r['status']) ?></td>
  <td class="right nowrap"><?php if ($r['status'] === 'pending' && $canApprove): ?>
    <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="request_id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="status" value="<?= e($status) ?>">
      <button name="action" value="approved" class="btn btn-success btn-sm"><i class="fa-solid fa-check"></i></button>
      <button name="action" value="rejected" class="btn btn-danger btn-sm"><i class="fa-solid fa-xmark"></i></button></form><?php endif; ?></td>
</tr><?php endforeach; if (!$rows): ?><tr><td colspan="7" class="center muted">No requests.</td></tr><?php endif; ?></tbody></table></div>
