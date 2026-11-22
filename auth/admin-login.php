<?php
require_once dirname(__DIR__) . '/includes/auth.php';
if (is_logged_in()) redirect(home_for(current_user()['role']));

$email = '';
if (is_post()) {
    csrf_check();
    $email = strtolower(input('email'));
    $pass  = $_POST['password'] ?? '';
    if (login_throttled()) {
        flash('error', 'Too many failed attempts. Please wait 5 minutes.');
    } else {
        $s = db()->prepare('SELECT * FROM users WHERE email=? AND role IN ("admin","coordinator") LIMIT 1');
        $s->execute([$email]);
        $u = $s->fetch();
        if ($u && $u['status'] === 'active' && password_verify($pass, $u['password_hash'])) {
            unset($_SESSION['login_fail']);
            login_user($u);
            redirect(home_for($u['role']));
        }
        login_failed();
        flash('error', 'Invalid staff credentials.');
    }
}
$pageTitle = 'Staff portal';
$bodyClass = 'auth-page';
include dirname(__DIR__) . '/includes/header.php';
?>
<div class="auth-wrap single">
  <form class="card auth-card" method="post" novalidate>
    <span class="role-pill">Staff portal</span>
    <h1>Admin &amp; Coordinator sign in</h1>
    <?= csrf_field() ?>
    <label class="field"><span>Email</span><input type="email" name="email" value="<?= e($email) ?>" required autofocus></label>
    <label class="field"><span>Password</span><input type="password" name="password" required></label>
    <button class="btn btn-primary btn-block" type="submit">Sign in</button>
    <p class="muted center small"><a href="<?= e(url('auth/login.php')) ?>">&larr; Student sign in</a></p>
  </form>
</div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
