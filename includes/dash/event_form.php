<?php
/** Add / edit event. Expects $user, $scopeClubId, $base. */
$pdo = db();
$id = input_int('id');
$ev = ['club_id' => $scopeClubId ?? '', 'title' => '', 'description' => '', 'venue' => '', 'event_date' => '', 'capacity' => ''];
if ($id) {
    $s = $pdo->prepare('SELECT * FROM events WHERE id=?'); $s->execute([$id]);
    $ev = $s->fetch();
    if (!$ev || ($scopeClubId !== null && (int)$ev['club_id'] !== $scopeClubId)) { http_response_code(404); exit('Event not found.'); }
}
$clubs = $scopeClubId === null ? $pdo->query('SELECT id,name FROM clubs WHERE status="active" ORDER BY name')->fetchAll() : [];
$errors = [];
if (is_post()) {
    csrf_check();
    $ev['club_id'] = $scopeClubId ?? input_int('club_id');
    $ev['title'] = input('title'); $ev['description'] = input('description'); $ev['venue'] = input('venue');
    $ev['event_date'] = input('event_date'); $ev['capacity'] = input('capacity');
    $ts = strtotime($ev['event_date']);
    if (mb_strlen($ev['title']) < 3 || mb_strlen($ev['title']) > 150) $errors[] = 'Title must be 3-150 characters.';
    if (!$ts) $errors[] = 'Please choose a valid date and time.';
    elseif (!$id && $ts < time()) $errors[] = 'Event date must be in the future.';
    if ($ev['capacity'] !== '' && (!ctype_digit($ev['capacity']) || (int)$ev['capacity'] < 1)) $errors[] = 'Capacity must be a positive number (or empty for unlimited).';
    $c = $pdo->prepare('SELECT 1 FROM clubs WHERE id=?'); $c->execute([$ev['club_id']]);
    if (!$c->fetch()) $errors[] = 'Please choose a club.';
    if (!$errors) {
        $date = date('Y-m-d H:i:s', $ts); $cap = $ev['capacity'] === '' ? null : (int)$ev['capacity'];
        if ($id) $pdo->prepare('UPDATE events SET club_id=?,title=?,description=?,venue=?,event_date=?,capacity=? WHERE id=?')->execute([$ev['club_id'], $ev['title'], $ev['description'], $ev['venue'] ?: null, $date, $cap, $id]);
        else $pdo->prepare('INSERT INTO events (club_id,title,description,venue,event_date,capacity,created_by) VALUES (?,?,?,?,?,?,?)')->execute([$ev['club_id'], $ev['title'], $ev['description'], $ev['venue'] ?: null, $date, $cap, $user['id']]);
        if (!$id) { // tell members
            $m = $pdo->prepare('SELECT user_id FROM club_members WHERE club_id=?'); $m->execute([$ev['club_id']]);
            foreach ($m->fetchAll(PDO::FETCH_COLUMN) as $uid) if ((int)$uid !== (int)$user['id']) notify((int)$uid, 'New event: ' . $ev['title'], fmt_date($date) . ' at ' . fmt_time($date), 'events.php');
        }
        flash('success', $id ? 'Event updated.' : 'Event created.');
        redirect($base . '/events.php');
    }
}
$dtValue = $ev['event_date'] ? date('Y-m-d\TH:i', strtotime($ev['event_date'])) : '';
?>
<?php foreach ($errors as $er): ?><div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i><span><?= e($er) ?></span></div><?php endforeach; ?>
<form method="post" class="card form-stack"><?= csrf_field() ?>
  <?php if ($clubs): ?><label class="field"><span>Club *</span><select name="club_id" required><option value="">Select club</option><?php foreach ($clubs as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)$ev['club_id'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></label><?php endif; ?>
  <label class="field"><span>Title *</span><input name="title" value="<?= e($ev['title']) ?>" required maxlength="150"></label>
  <label class="field"><span>Description</span><textarea name="description" rows="4"><?= e($ev['description']) ?></textarea></label>
  <div class="grid-3">
    <label class="field"><span>Date &amp; time *</span><input type="datetime-local" name="event_date" value="<?= e($dtValue) ?>" required></label>
    <label class="field"><span>Venue</span><input name="venue" value="<?= e($ev['venue']) ?>" maxlength="150"></label>
    <label class="field"><span>Capacity</span><input type="number" min="1" name="capacity" value="<?= e((string)$ev['capacity']) ?>" placeholder="Unlimited"></label>
  </div>
  <div class="form-actions"><button class="btn btn-primary">Save event</button><a class="btn btn-ghost" href="<?= e(url($base . '/events.php')) ?>">Cancel</a></div>
</form>
