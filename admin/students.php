<?php
require_once dirname(__DIR__) . '/includes/auth.php';
$user = require_role('admin');
$pdo = db();
if (is_post()) {
    csrf_check();
    $act = input('action'); $id = input_int('user_id');
    if ($act === 'create_coordinator') {
        $name = input('name'); $email = strtolower(input('email')); $pass = $_POST['password'] ?? '';
        if (mb_strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 8) flash('error', 'Provide a name, valid email and a password of 8+ characters.');
        else try {
            $pdo->prepare('INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,"coordinator")')->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT)]);
            flash('success', 'Coordinator account created. Assign it to a club from the club edit page.');
        } catch (PDOException $ex) { flash('error', 'That email is already registered.'); }
    } else {
        $t = $pdo->prepare('SELECT role FROM users WHERE id=?'); $t->execute([$id]);
        $role = $t->fetchColumn();
        if (!in_array($role, ['student', 'coordinator'], true)) flash('error', 'Invalid user.');
        elseif ($act === 'block' || $act === 'unblock') { $pdo->prepare('UPDATE users SET status=? WHERE id=?')->execute([$act === 'block' ? 'blocked' : 'active', $id]); flash('success', 'User updated.'); }
        elseif ($act === 'delete') { $pdo->prepare('DELETE FROM users WHERE id=?')->execute([$id]); flash('success', 'User deleted.'); }
    }
    redirect('admin/students.php');
}
$q = input('q');
$st = $pdo->prepare('SELECT u.*, sp.department, sp.year_of_study, sp.roll_number, (SELECT COUNT(*) FROM club_members m WHERE m.user_id=u.id) clubs
    FROM users u LEFT JOIN student_profiles sp ON sp.user_id=u.id WHERE u.role IN ("student","coordinator") AND (u.name LIKE ? OR u.email LIKE ? OR sp.roll_number LIKE ?) ORDER BY u.created_at DESC LIMIT 300');
$l = like_escape($q); $st->execute([$l, $l, $l]);
$rows = $st->fetchAll();
$pageTitle = 'Students'; $active = 'students';
include dirname(__DIR__) . '/includes/dash_header.php';
?>
<form class="inline-filter" method="get"><input type="search" name="q" value="<?= e($q) ?>" placeholder="Name, email or roll number"><button class="btn btn-ghost">Search</button></form>
<div class="table-wrap"><table class="table"><thead><tr><th>User</th><th>Role</th><th>Department</th><th>Clubs</th><th>Joined</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><b><?= e($r['name']) ?></b><br><small class="muted"><?= e($r['email']) ?><?= $r['roll_number'] ? ' · ' . e($r['roll_number']) : '' ?></small></td>
  <td><?= e(ucfirst($r['role'])) ?></td><td><?= e($r['department'] ?: '—') ?></td><td><?= (int)$r['clubs'] ?></td><td><?= e(fmt_date($r['created_at'])) ?></td><td><?= status_badge($r['status'] === 'active' ? 'approved' : 'rejected') ?></td>
  <td class="right nowrap"><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="user_id" value="<?= (int)$r['id'] ?>">
    <button class="btn btn-ghost btn-sm" name="action" value="<?= $r['status'] === 'active' ? 'block' : 'unblock' ?>"><?= $r['status'] === 'active' ? 'Block' : 'Unblock' ?></button></form>
    <form method="post" class="inline" data-confirm="Permanently delete <?= e($r['name']) ?>?"><?= csrf_field() ?><input type="hidden" name="user_id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="action" value="delete"><button class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button></form></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="7" class="center muted">No users found.</td></tr><?php endif; ?></tbody></table></div>
<details class="card"><summary><b>Create a coordinator account</b></summary>
  <form method="post" class="form-stack"><?= csrf_field() ?><input type="hidden" name="action" value="create_coordinator"><div class="grid-3">
    <label class="field"><span>Name</span><input name="name" required></label><label class="field"><span>Email</span><input type="email" name="email" required></label><label class="field"><span>Password</span><input type="password" name="password" minlength="8" required></label></div>
    <button class="btn btn-primary">Create coordinator</button></form></details>
<?php include dirname(__DIR__) . '/includes/dash_footer.php'; ?>
