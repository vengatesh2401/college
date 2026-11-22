<?php
/** Registered members. Expects $user, $scopeClubId, $base. Admin may remove members. */
$pdo = db();
if (is_post() && $base === 'admin') {
    csrf_check();
    $pdo->prepare('DELETE FROM club_members WHERE id=?')->execute([input_int('member_id')]);
    flash('success', 'Member removed.');
    redirect('admin/members.php?club=' . input_int('club'));
}
$clubFilter = $scopeClubId ?? input_int('club');
$q = input('q');
$where = []; $p = [];
if ($clubFilter) { $where[] = 'm.club_id=?'; $p[] = $clubFilter; }
if ($q !== '') { $where[] = '(u.name LIKE ? OR u.email LIKE ?)'; $p[] = like_escape($q); $p[] = like_escape($q); }
$st = $pdo->prepare('SELECT m.id, m.joined_at, u.name, u.email, u.role, sp.department, sp.year_of_study, c.name AS club_name FROM club_members m
    JOIN users u ON u.id=m.user_id LEFT JOIN student_profiles sp ON sp.user_id=u.id JOIN clubs c ON c.id=m.club_id'
    . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY m.joined_at DESC LIMIT 300');
$st->execute($p);
$rows = $st->fetchAll();
$clubList = $scopeClubId === null ? $pdo->query('SELECT id,name FROM clubs ORDER BY name')->fetchAll() : [];
?>
<form class="inline-filter" method="get"><input type="search" name="q" value="<?= e($q) ?>" placeholder="Search member">
  <?php if ($clubList): ?><select name="club"><option value="0">All clubs</option><?php foreach ($clubList as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $clubFilter === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select><?php endif; ?>
  <button class="btn btn-primary">Filter</button></form>
<p class="muted"><?= count($rows) ?> member<?= count($rows) === 1 ? '' : 's' ?></p>
<div class="table-wrap"><table class="table"><thead><tr><th>Name</th><th>Club</th><th>Department</th><th>Year</th><th>Joined</th><?php if ($base === 'admin'): ?><th></th><?php endif; ?></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><b><?= e($r['name']) ?></b><br><small class="muted"><?= e($r['email']) ?></small></td><td><?= e($r['club_name']) ?></td><td><?= e($r['department'] ?: '—') ?></td><td><?= $r['year_of_study'] ? 'Year ' . (int)$r['year_of_study'] : '—' ?></td><td><?= e(fmt_date($r['joined_at'])) ?></td>
  <?php if ($base === 'admin'): ?><td class="right"><form method="post" data-confirm="Remove this member from the club?"><?= csrf_field() ?><input type="hidden" name="member_id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="club" value="<?= (int)$clubFilter ?>"><button class="btn btn-danger btn-sm"><i class="fa-solid fa-user-minus"></i></button></form></td><?php endif; ?></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="6" class="center muted">No members found.</td></tr><?php endif; ?></tbody></table></div>
