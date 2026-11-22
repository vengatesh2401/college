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
        $s = db()->prepare('SELECT * FROM users WHERE email=? AND role="student" LIMIT 1');
        $s->execute([$email]);
        $u = $s->fetch();
        if ($u && $u['status'] === 'active' && password_verify($pass, $u['password_hash'])) {
            if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
                db()->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($pass, PASSWORD_DEFAULT), $u['id']]);
            }
            unset($_SESSION['login_fail']);
            login_user($u);
            flash('success', 'Welcome back, ' . explode(' ', $u['name'])[0] . '!');
            redirect('student/dashboard.php');
        }
        login_failed();
        flash('error', ($u && $u['status'] === 'blocked') ? 'This account has been blocked. Contact the administrator.' : 'Invalid email or password.');
    }
}
$pageTitle = 'Sign in';
$bodyClass = 'auth-page';
include dirname(__DIR__) . '/includes/header.php';
?>
<div class="auth-wrap">
  <div class="auth-art">
    <h2>Your campus,<br>your crew.</h2>
    <p>Sign in to track memberships, register for events and get club suggestions made for you.</p>
    <ul class="auth-points"><li><i class="fa-solid fa-check"></i> Personalised recommendations</li><li><i class="fa-solid fa-check"></i> One-tap event registration</li><li><i class="fa-solid fa-check"></i> Live membership status</li></ul>
  </div>
  <form class="card auth-card" method="post" novalidate>
    <h1>Student sign in</h1>
    <?= csrf_field() ?>
    <label class="field"><span>Email</span><input type="email" name="email" value="<?= e($email) ?>" required autofocus placeholder="you@college.edu"></label>
    <label class="field"><span>Password</span><input type="password" name="password" required placeholder="••••••••"></label>
    <button class="btn btn-primary btn-block" type="submit">Sign in</button>
    <p class="muted center">New here? <a href="<?= e(url('auth/register.php')) ?>">Create an account</a></p>
    <p class="muted center small">Admin or coordinator? <a href="<?= e(url('auth/admin-login.php')) ?>">Staff portal</a></p>
  </form>
</div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
