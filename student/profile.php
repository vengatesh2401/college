<?php
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/queries.php';
$u = require_role('student');
$pdo = db();
$errors = [];

if (is_post()) {
    csrf_check();
    $form = input('form');
    if ($form === 'profile') {
        $name = input('name'); $dept = input('department'); $year = input_int('year_of_study');
        $roll = input('roll_number'); $phone = input('phone'); $career = input('career_goal'); $bio = input('bio');
        $skills = normalize_tags(input('skills'));
        $interests = array_values(array_unique(array_map('intval', (array)($_POST['interests'] ?? []))));

        if (mb_strlen($name) < 2 || mb_strlen($name) > 100) $errors[] = 'Name must be 2-100 characters.';
        if (!valid_department($dept)) $errors[] = 'Invalid department.';
        if ($year && ($year < 1 || $year > 6)) $errors[] = 'Year must be between 1 and 6.';
        if ($phone !== '' && !preg_match('/^[0-9+\-\s]{7,20}$/', $phone)) $errors[] = 'Phone number looks invalid.';
        if (mb_strlen($bio) > 1000 || mb_strlen($career) > 200 || mb_strlen($roll) > 40 || mb_strlen($skills) > 500) $errors[] = 'One of the fields is too long.';
        if ($interests) { // keep only real category ids
            $valid = array_map('intval', $pdo->query('SELECT id FROM club_categories')->fetchAll(PDO::FETCH_COLUMN));
            $interests = array_values(array_intersect($interests, $valid));
        }
        if (!$errors) {
            $pdo->beginTransaction();
            try {
                $pdo->prepare('UPDATE users SET name=? WHERE id=?')->execute([$name, $u['id']]);
                $pdo->prepare('INSERT INTO student_profiles (user_id,roll_number,department,year_of_study,phone,skills,career_goal,bio) VALUES (?,?,?,?,?,?,?,?)
                    ON DUPLICATE KEY UPDATE roll_number=VALUES(roll_number), department=VALUES(department), year_of_study=VALUES(year_of_study),
                    phone=VALUES(phone), skills=VALUES(skills), career_goal=VALUES(career_goal), bio=VALUES(bio)')
                    ->execute([$u['id'], $roll ?: null, $dept ?: null, $year ?: null, $phone ?: null, $skills ?: null, $career ?: null, $bio ?: null]);
                $pdo->prepare('DELETE FROM student_interests WHERE user_id=?')->execute([$u['id']]);
                $ins = $pdo->prepare('INSERT INTO student_interests (user_id,category_id) VALUES (?,?)');
                foreach ($interests as $cid) $ins->execute([$u['id'], $cid]);
                $pdo->commit();
            } catch (Throwable $ex) { $pdo->rollBack(); throw $ex; }
            flash('success', 'Profile updated.');
            redirect('student/profile.php');
        }
    } elseif ($form === 'password') {
        $cur = $_POST['current'] ?? ''; $new = $_POST['new'] ?? ''; $new2 = $_POST['new2'] ?? '';
        $h = $pdo->prepare('SELECT password_hash FROM users WHERE id=?'); $h->execute([$u['id']]);
        if (!password_verify($cur, (string)$h->fetchColumn())) $errors[] = 'Current password is incorrect.';
        elseif (strlen($new) < 8 || !preg_match('/[A-Za-z]/', $new) || !preg_match('/\d/', $new)) $errors[] = 'New password needs 8+ characters with a letter and a number.';
        elseif ($new !== $new2) $errors[] = 'New passwords do not match.';
        else {
            $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($new, PASSWORD_DEFAULT), $u['id']]);
            flash('success', 'Password changed.');
            redirect('student/profile.php');
        }
    }
}

$p = $pdo->prepare('SELECT u.name,u.email,sp.* FROM users u LEFT JOIN student_profiles sp ON sp.user_id=u.id WHERE u.id=?');
$p->execute([$u['id']]);
$profile = $p->fetch();
$mine = $pdo->prepare('SELECT category_id FROM student_interests WHERE user_id=?');
$mine->execute([$u['id']]);
$myInterests = array_map('intval', $mine->fetchAll(PDO::FETCH_COLUMN));
$cats = all_categories();
$skillSuggest = all_skills();

$pageTitle = 'Profile & Interests';
include dirname(__DIR__) . '/includes/header.php';
?>
<section class="page-head"><div class="container"><h1>Profile &amp; interests</h1><p class="muted">The more we know, the better your club suggestions.</p></div></section>
<div class="container narrow-2">
  <?php foreach ($errors as $er): ?><div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i><span><?= e($er) ?></span></div><?php endforeach; ?>
  <form method="post" class="card form-stack">
    <?= csrf_field() ?><input type="hidden" name="form" value="profile">
    <h2>About you</h2>
    <div class="grid-2">
      <label class="field"><span>Full name</span><input name="name" value="<?= e($profile['name']) ?>" required maxlength="100"></label>
      <label class="field"><span>Email</span><input value="<?= e($profile['email']) ?>" disabled></label>
      <label class="field"><span>Roll number</span><input name="roll_number" value="<?= e($profile['roll_number']) ?>" maxlength="40"></label>
      <label class="field"><span>Phone</span><input name="phone" value="<?= e($profile['phone']) ?>" maxlength="20"></label>
      <label class="field"><span>Department</span><select name="department"><option value="">Select</option><?php foreach (DEPARTMENTS as $d): ?><option <?= $d === $profile['department'] ? 'selected' : '' ?>><?= e($d) ?></option><?php endforeach; ?></select></label>
      <label class="field"><span>Year</span><select name="year_of_study"><option value="">—</option><?php for ($i = 1; $i <= 6; $i++): ?><option value="<?= $i ?>" <?= (int)$profile['year_of_study'] === $i ? 'selected' : '' ?>>Year <?= $i ?></option><?php endfor; ?></select></label>
    </div>
    <label class="field"><span>Career goal</span><input name="career_goal" value="<?= e($profile['career_goal']) ?>" maxlength="200" placeholder="e.g. Data Scientist"></label>
    <label class="field"><span>Skills <small>(comma separated)</small></span><input name="skills" value="<?= e($profile['skills']) ?>" list="skill-list" maxlength="500" placeholder="Python, Photography, Public Speaking">
      <datalist id="skill-list"><?php foreach ($skillSuggest as $s): ?><option value="<?= e($s) ?>"><?php endforeach; ?></datalist></label>
    <label class="field"><span>Bio</span><textarea name="bio" rows="3" maxlength="1000"><?= e($profile['bio']) ?></textarea></label>
    <h2>My interests</h2>
    <div class="interest-picker">
      <?php foreach ($cats as $c): ?><label class="interest" style="--lc:<?= e($c['color']) ?>"><input type="checkbox" name="interests[]" value="<?= (int)$c['id'] ?>" <?= in_array((int)$c['id'], $myInterests, true) ? 'checked' : '' ?>><span><i class="fa-solid <?= e($c['icon']) ?>"></i> <?= e($c['name']) ?></span></label><?php endforeach; ?>
    </div>
    <button class="btn btn-primary" type="submit">Save profile</button>
  </form>

  <form method="post" class="card form-stack">
    <?= csrf_field() ?><input type="hidden" name="form" value="password">
    <h2>Change password</h2>
    <div class="grid-3">
      <label class="field"><span>Current</span><input type="password" name="current" required></label>
      <label class="field"><span>New</span><input type="password" name="new" required minlength="8"></label>
      <label class="field"><span>Confirm new</span><input type="password" name="new2" required minlength="8"></label>
    </div>
    <button class="btn btn-ghost" type="submit">Update password</button>
  </form>
</div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
