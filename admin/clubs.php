<?php
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/queries.php';
$user = require_role('admin');
$pdo = db();
if (is_post()) {
    csrf_check();
    $id = input_int('club_id');
    $s = $pdo->prepare('SELECT logo FROM clubs WHERE id=?'); $s->execute([$id]);
    if ($row = $s->fetch()) { delete_logo($row['logo']); $pdo->prepare('DELETE FROM clubs WHERE id=?')->execute([$id]); flash('success', 'Club deleted.'); }
    redirect('admin/clubs.php');
}
$q = input('q');
$st = $pdo->prepare(CLUB_SELECT . ' WHERE (c.name LIKE ?) ORDER BY c.created_at DESC');
$st->execute([like_escape($q)]);
$rows = $st->fetchAll();
$coordNames = $pdo->query('SELECT id,name FROM users WHERE role="coordinator"')->fetchAll(PDO::FETCH_KEY_PAIR);
$pageTitle = 'Clubs'; $active = 'clubs';
include dirname(__DIR__) . '/includes/dash_header.php';
?>
<div class="toolbar"><form class="inline-filter" method="get"><input type="search" name="q" value="<?= e($q) ?>" placeholder="Search clubs"><button class="btn btn-ghost">Search</button></form>
  <a class="btn btn-primary" href="<?= e(url('admin/club-form.php')) ?>"><i class="fa-solid fa-plus"></i> Add club</a></div>
<div class="table-wrap"><table class="table"><thead><tr><th>Club</th><th>Category</th><th>Coordinator</th><th>Members</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $c): ?><tr>
  <td><a class="inline-club" href="<?= e(url('club-details.php?slug=' . urlencode($c['slug']))) ?>"><?= club_logo($c, 'sm') ?><b><?= e($c['name']) ?></b></a></td>
  <td><?= e($c['cat_name']) ?></td><td><?= e($coordNames[$c['coordinator_user_id']] ?? '—') ?></td><td><?= (int)$c['member_count'] ?></td><td><?= status_badge($c['status'] === 'active' ? 'approved' : 'rejected') ?></td>
  <td class="right nowrap"><a class="btn btn-ghost btn-sm" href="<?= e(url('admin/club-form.php?id=' . (int)$c['id'])) ?>"><i class="fa-solid fa-pen"></i></a>
    <form method="post" class="inline" data-confirm="Delete <?= e($c['name']) ?>? All its members, requests and events will be removed."><?= csrf_field() ?><input type="hidden" name="club_id" value="<?= (int)$c['id'] ?>"><button class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button></form></td>
</tr><?php endforeach; if (!$rows): ?><tr><td colspan="6" class="center muted">No clubs found.</td></tr><?php endif; ?></tbody></table></div>
<?php include dirname(__DIR__) . '/includes/dash_footer.php'; ?>
