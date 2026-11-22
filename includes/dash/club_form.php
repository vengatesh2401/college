<?php
/** Shared club add/edit form. Expects: $club (array|null), $isAdmin (bool), $returnTo (string). */
$pdo = db();
$errors = [];
$cats = all_categories();
$coords = $isAdmin ? $pdo->query('SELECT id,name,email FROM users WHERE role="coordinator" ORDER BY name')->fetchAll() : [];
$v = $club ?? ['name' => '', 'category_id' => '', 'tagline' => '', 'description' => '', 'faculty_coordinator' => '', 'student_coordinator' => '', 'activities' => '',
    'requirements' => '', 'contact_email' => '', 'contact_phone' => '', 'meeting_info' => '', 'department' => '', 'skills' => '', 'status' => 'active',
    'coordinator_user_id' => '', 'coordinator_can_approve' => 1, 'logo' => null];

if (is_post()) {
    csrf_check();
    foreach (['name', 'tagline', 'description', 'faculty_coordinator', 'student_coordinator', 'activities', 'requirements', 'contact_email', 'contact_phone', 'meeting_info', 'department'] as $k) $v[$k] = input($k);
    $v['skills'] = normalize_tags(input('skills'));
    if ($isAdmin) {
        $v['category_id'] = input_int('category_id');
        $v['status'] = input('status') === 'inactive' ? 'inactive' : 'active';
        $v['coordinator_user_id'] = input_int('coordinator_user_id') ?: null;
        $v['coordinator_can_approve'] = isset($_POST['coordinator_can_approve']) ? 1 : 0;
    }
    if (mb_strlen($v['name']) < 3 || mb_strlen($v['name']) > 120) $errors[] = 'Club name must be 3-120 characters.';
    if (mb_strlen($v['description']) < 20) $errors[] = 'Description must be at least 20 characters.';
    if ($isAdmin) {
        $ok = $pdo->prepare('SELECT 1 FROM club_categories WHERE id=?'); $ok->execute([$v['category_id']]);
        if (!$ok->fetch()) $errors[] = 'Please choose a category.';
        if ($v['coordinator_user_id']) {
            $ok = $pdo->prepare('SELECT 1 FROM users WHERE id=? AND role="coordinator"'); $ok->execute([$v['coordinator_user_id']]);
            if (!$ok->fetch()) $errors[] = 'Invalid coordinator selected.';
        }
    }
    if ($v['contact_email'] !== '' && !filter_var($v['contact_email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Contact email is invalid.';
    if ($v['contact_phone'] !== '' && !preg_match('/^[0-9+\-\s]{7,20}$/', $v['contact_phone'])) $errors[] = 'Contact phone is invalid.';
    if (!valid_department($v['department'])) $errors[] = 'Invalid department.';
    if (mb_strlen($v['tagline']) > 160) $errors[] = 'Tagline is too long (max 160).';

    $newLogo = null;
    if (!$errors) { try { $newLogo = save_logo($_FILES['logo'] ?? ['error' => UPLOAD_ERR_NO_FILE]); } catch (RuntimeException $ex) { $errors[] = $ex->getMessage(); } }

    if (!$errors) {
        $logo = $v['logo'];
        if ($newLogo) { delete_logo($logo); $logo = $newLogo; }
        elseif (!empty($_POST['remove_logo'])) { delete_logo($logo); $logo = null; }
        $dept = $v['department'] ?: null;
        if ($club) {
            if ($isAdmin) {
                $pdo->prepare('UPDATE clubs SET name=?,category_id=?,tagline=?,description=?,faculty_coordinator=?,student_coordinator=?,activities=?,requirements=?,contact_email=?,contact_phone=?,meeting_info=?,department=?,skills=?,logo=?,status=?,coordinator_user_id=?,coordinator_can_approve=? WHERE id=?')
                    ->execute([$v['name'], $v['category_id'], $v['tagline'], $v['description'], $v['faculty_coordinator'], $v['student_coordinator'], $v['activities'], $v['requirements'], $v['contact_email'], $v['contact_phone'], $v['meeting_info'], $dept, $v['skills'], $logo, $v['status'], $v['coordinator_user_id'], $v['coordinator_can_approve'], $club['id']]);
            } else {
                $pdo->prepare('UPDATE clubs SET name=?,tagline=?,description=?,faculty_coordinator=?,student_coordinator=?,activities=?,requirements=?,contact_email=?,contact_phone=?,meeting_info=?,department=?,skills=?,logo=? WHERE id=?')
                    ->execute([$v['name'], $v['tagline'], $v['description'], $v['faculty_coordinator'], $v['student_coordinator'], $v['activities'], $v['requirements'], $v['contact_email'], $v['contact_phone'], $v['meeting_info'], $dept, $v['skills'], $logo, $club['id']]);
            }
            flash('success', 'Club updated.');
        } else {
            $slug = slugify($v['name']); $base = $slug; $i = 2;
            $chk = $pdo->prepare('SELECT 1 FROM clubs WHERE slug=?');
            while (true) { $chk->execute([$slug]); if (!$chk->fetch()) break; $slug = $base . '-' . $i++; }
            $pdo->prepare('INSERT INTO clubs (category_id,coordinator_user_id,name,slug,tagline,description,logo,faculty_coordinator,student_coordinator,activities,requirements,contact_email,contact_phone,meeting_info,department,skills,coordinator_can_approve,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
                ->execute([$v['category_id'], $v['coordinator_user_id'], $v['name'], $slug, $v['tagline'], $v['description'], $logo, $v['faculty_coordinator'], $v['student_coordinator'], $v['activities'], $v['requirements'], $v['contact_email'], $v['contact_phone'], $v['meeting_info'], $dept, $v['skills'], $v['coordinator_can_approve'], $v['status']]);
            flash('success', 'Club created.');
        }
        redirect($returnTo);
    }
    $v['logo'] = $club['logo'] ?? null;
}
?>
<?php foreach ($errors as $er): ?><div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i><span><?= e($er) ?></span></div><?php endforeach; ?>
<form method="post" enctype="multipart/form-data" class="card form-stack">
  <?= csrf_field() ?>
  <div class="grid-2">
    <label class="field"><span>Club name *</span><input name="name" value="<?= e($v['name']) ?>" required maxlength="120"></label>
    <?php if ($isAdmin): ?>
    <label class="field"><span>Category *</span><select name="category_id" required><option value="">Select</option><?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)$v['category_id'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></label>
    <?php endif; ?>
  </div>
  <label class="field"><span>Tagline</span><input name="tagline" value="<?= e($v['tagline']) ?>" maxlength="160"></label>
  <label class="field"><span>Description *</span><textarea name="description" rows="4" required><?= e($v['description']) ?></textarea></label>
  <div class="grid-2">
    <label class="field"><span>Faculty coordinator</span><input name="faculty_coordinator" value="<?= e($v['faculty_coordinator']) ?>" maxlength="100"></label>
    <label class="field"><span>Student coordinator</span><input name="student_coordinator" value="<?= e($v['student_coordinator']) ?>" maxlength="100"></label>
    <label class="field"><span>Contact email</span><input type="email" name="contact_email" value="<?= e($v['contact_email']) ?>"></label>
    <label class="field"><span>Contact phone</span><input name="contact_phone" value="<?= e($v['contact_phone']) ?>"></label>
    <label class="field"><span>Meeting info</span><input name="meeting_info" value="<?= e($v['meeting_info']) ?>" placeholder="Wednesdays 5 PM, Lab 3"></label>
    <label class="field"><span>Preferred department</span><select name="department"><option value="">Open to all</option><?php foreach (DEPARTMENTS as $d): ?><option <?= $d === $v['department'] ? 'selected' : '' ?>><?= e($d) ?></option><?php endforeach; ?></select></label>
  </div>
  <label class="field"><span>Skills <small>(comma separated, used for filtering &amp; suggestions)</small></span><input name="skills" value="<?= e($v['skills']) ?>" placeholder="Python, Public Speaking"></label>
  <label class="field"><span>Activities <small>(one per line)</small></span><textarea name="activities" rows="4"><?= e($v['activities']) ?></textarea></label>
  <label class="field"><span>Membership requirements</span><textarea name="requirements" rows="2"><?= e($v['requirements']) ?></textarea></label>
  <div class="grid-2">
    <label class="field"><span>Logo <small>(JPG/PNG/WEBP, max 2 MB)</small></span><input type="file" name="logo" accept="image/jpeg,image/png,image/webp,image/gif">
      <?php if (!empty($v['logo'])): ?><span class="inline-club"><?= club_logo(['name' => $v['name'] ?: 'X', 'logo' => $v['logo']], 'sm') ?><label class="check"><input type="checkbox" name="remove_logo" value="1"><span>Remove current logo</span></label></span><?php endif; ?></label>
    <?php if ($isAdmin): ?>
    <div>
      <label class="field"><span>Club coordinator account</span><select name="coordinator_user_id"><option value="">— none —</option><?php foreach ($coords as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)$v['coordinator_user_id'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?> (<?= e($c['email']) ?>)</option><?php endforeach; ?></select></label>
      <label class="check"><input type="checkbox" name="coordinator_can_approve" value="1" <?= $v['coordinator_can_approve'] ? 'checked' : '' ?>><span>Coordinator may approve/reject requests</span></label>
      <label class="field"><span>Status</span><select name="status"><option value="active" <?= $v['status'] === 'active' ? 'selected' : '' ?>>Active</option><option value="inactive" <?= $v['status'] === 'inactive' ? 'selected' : '' ?>>Inactive (hidden)</option></select></label>
    </div>
    <?php endif; ?>
  </div>
  <div class="form-actions"><button class="btn btn-primary" type="submit">Save club</button><a class="btn btn-ghost" href="<?= e(url($returnTo)) ?>">Cancel</a></div>
</form>
