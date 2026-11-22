<?php
require_once dirname(__DIR__) . '/includes/auth.php';
if (!is_post()) json_out(['ok' => false, 'message' => 'POST required.'], 405);
csrf_check(true);
$u = current_user();
if (!$u) json_out(['ok' => false, 'message' => 'Please sign in first.'], 401);
if ($u['role'] !== 'student') json_out(['ok' => false, 'message' => 'Only students can register.'], 403);

$eventId = input_int('event_id');
$pdo = db();
$pdo->beginTransaction();
try {
    $s = $pdo->prepare('SELECT * FROM events WHERE id=? FOR UPDATE');
    $s->execute([$eventId]);
    $ev = $s->fetch();
    if (!$ev) { $pdo->rollBack(); json_out(['ok' => false, 'message' => 'Event not found.'], 404); }

    $has = $pdo->prepare('SELECT id FROM event_registrations WHERE event_id=? AND user_id=?');
    $has->execute([$eventId, $u['id']]);
    $existing = $has->fetchColumn();

    if ($existing) {                       // toggle: cancel registration
        $pdo->prepare('DELETE FROM event_registrations WHERE id=?')->execute([$existing]);
        $registered = false; $msg = 'Registration cancelled.';
    } else {
        if (strtotime($ev['event_date']) < time()) { $pdo->rollBack(); json_out(['ok' => false, 'message' => 'This event has already happened.'], 422); }
        $c = $pdo->prepare('SELECT COUNT(*) FROM event_registrations WHERE event_id=?');
        $c->execute([$eventId]);
        if ($ev['capacity'] !== null && (int)$c->fetchColumn() >= (int)$ev['capacity']) { $pdo->rollBack(); json_out(['ok' => false, 'message' => 'Sorry, this event is full.'], 409); }
        $pdo->prepare('INSERT INTO event_registrations (event_id,user_id) VALUES (?,?)')->execute([$eventId, $u['id']]);
        $registered = true; $msg = 'You are registered for ' . $ev['title'] . '!';
    }
    $pdo->commit();
} catch (Throwable $ex) { $pdo->rollBack(); json_out(['ok' => false, 'message' => 'Something went wrong.'], 500); }

$c = $pdo->prepare('SELECT COUNT(*) FROM event_registrations WHERE event_id=?');
$c->execute([$eventId]);
json_out(['ok' => true, 'registered' => $registered, 'count' => (int)$c->fetchColumn(), 'message' => $msg]);
