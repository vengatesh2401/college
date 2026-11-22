<?php
require_once dirname(__DIR__) . '/includes/auth.php';
if (is_logged_in()) redirect(home_for(current_user()['role']));

$f = ['name' => '', 'email' => '', 'roll_number' => '', 'department' => '', 'year_of_study' => ''];
$errors = [];
if (is_post()) {
    csrf_check();
    foreach ($f as $k => $_) $f[$k] = input($k);
    $f['email'] = strtolower($f['email']);
    $pass = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';
    $year = (int)$f['year_of_study'];

    if (mb_strlen($f['name']) < 2 || mb_strlen($f['name']) > 100) $errors[] = 'Please enter your full name (2-100 characters).';
    if (!filter_var($f['email'], FILTER_VALIDATE_EMAIL) || strlen($f['email']) > 150) $errors[] = 'Please enter a valid email address.';
    if (strlen($pass) < 8 || !preg_match('/[A-Za-z]/', $pass) || !preg_match('/\d/', $pass)) $errors[] = 'Password must be at least 8 characters and contain a letter and a number.';
    if ($pass !== $pass2) $errors[] = 'Passwords do not match.';
    if (!valid_department($f['department'])) $errors[] = 'Please choose a valid department.';
    if ($year && ($year < 1 || $year > 6)) $errors[] = 'Year of study must be between 1 and 6.';
    if (mb_strlen($f['roll_number']) > 40) $errors[] = 'Roll number is too long.';

    if (!$errors) {
        $s = db()->prepare('SELECT 1 FROM users WHERE email=?');
        $s->execute([$f['email']]);
        if ($s->fetch()) $errors[] = 'An account with this email already exists.';
    }
    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,"student")')
                ->execute([$f['name'], $f['email'], password_hash($pass, PASSWORD_DEFAULT)]);
            $uid = (int)$pdo->lastInsertId();
            $pdo->prepare('INSERT INTO student_profiles (user_id,roll_number,department,year_of_study) VALUES (?,?,?,?)')
                ->execute([$uid, $f['roll_number'] ?: null, $f['department'] ?: null, $year ?: null]);
            $pdo->commit();
        } catch (Throwable $ex) { $pdo->rollBack(); throw $ex; }
        notify($uid, 'Welcome to ' . APP_NAME, 'Pick your interests to get personalised club suggestions.', 'student/profile.php');
        login_user(['id' => $uid, 'role' => 'student']);
        flash('success', 'Account created! Pick your interests to get suggestions.');
        redirect('student/profile.php');
    }
}
$pageTitle = 'Create account';
$bodyClass = 'auth-page';
include dirname(__DIR__) . '/includes/header.php';
?>
<div class="auth-wrap">
  <div class="auth-art">
    <h2>Join the<br>community.</h2>
    <p>Create a free student account to join clubs, register for events and get suggestions based on your interests.</p>
  </div>
  <form class="card auth-card" method="post" novalidate>
    <h1>Create your account</h1>
    <?php foreach ($errors as $er): ?><div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i><span><?= e($er) ?></span></div><?php endforeach; ?>
    <?= csrf_field() ?>
    <label class="field"><span>Full name</span><input name="name" value="<?= e($f['name']) ?>" required maxlength="100"></label>
    <label class="field"><span>College email</span><input type="email" name="email" value="<?= e($f['email']) ?>" required maxlength="150"></label>
    <div class="grid-2">
      <label class="field"><span>Roll number</span><input name="roll_number" value="<?= e($f['roll_number']) ?>" maxlength="40"></label>
      <label class="field"><span>Year</span>
        <select name="year_of_study"><option value="">—</option><?php for ($i = 1; $i <= 6; $i++): ?><option value="<?= $i ?>" <?= (string)$i === $f['year_of_study'] ? 'selected' : '' ?>>Year <?= $i ?></option><?php endfor; ?></select></label>
    </div>
    <label class="field"><span>Department</span>
      <select name="department"><option value="">Select department</option><?php foreach (DEPARTMENTS as $d): ?><option <?= $d === $f['department'] ? 'selected' : '' ?>><?= e($d) ?></option><?php endforeach; ?></select></label>
    <div class="grid-2">
      <label class="field"><span>Password</span><input type="password" name="password" required minlength="8"></label>
      <label class="field"><span>Confirm</span><input type="password" name="password2" required minlength="8"></label>
    </div>
    <button class="btn btn-primary btn-block" type="submit">Create account</button>
    <p class="muted center">Already registered? <a href="<?= e(url('auth/login.php')) ?>">Sign in</a></p>
  </form>
</div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
