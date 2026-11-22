<?php
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/queries.php';
$u = require_role('student');
if (!is_post()) redirect('clubs.php');
csrf_check();

$clubId = input_int('club_id');
$action = input('action');
$pdo = db();
$s = $pdo->prepare('SELECT id,name,slug,coordinator_user_id FROM clubs WHERE id=? AND status="active"');
$s->execute([$clubId]);
$club = $s->fetch();
if (!$club) { flash('error', 'Club not found.'); redirect('clubs.php'); }
$back = 'club-details.php?slug=' . urlencode($club['slug']);
$state = membership_state((int)$u['id'], $clubId);

switch ($action) {
    case 'join':
        if ($state === 'member') { flash('info', 'You are already a member.'); break; }
        if ($state === 'pending') { flash('info', 'Your request is already pending.'); break; }
        $msg = mb_substr(input('message'), 0, 500);
        $pdo->prepare('INSERT INTO membership_requests (club_id,user_id,message,status) VALUES (?,?,?,"pending")
                       ON DUPLICATE KEY UPDATE message=VALUES(message), status="pending", reviewed_by=NULL, reviewed_at=NULL, created_at=NOW()')
            ->execute([$clubId, $u['id'], $msg ?: null]);
        if ($club['coordinator_user_id']) notify((int)$club['coordinator_user_id'], 'New membership request', $u['name'] . ' wants to join ' . $club['name'] . '.', 'coordinator/requests.php');
        flash('success', 'Request sent! You will be notified when it is reviewed.');
        break;
    case 'cancel':
        $pdo->prepare('DELETE FROM membership_requests WHERE club_id=? AND user_id=? AND status="pending"')->execute([$clubId, $u['id']]);
        flash('info', 'Request cancelled.');
        break;
    case 'leave':
        $pdo->prepare('DELETE FROM club_members WHERE club_id=? AND user_id=?')->execute([$clubId, $u['id']]);
        $pdo->prepare('DELETE FROM membership_requests WHERE club_id=? AND user_id=?')->execute([$clubId, $u['id']]);
        flash('info', 'You have left ' . $club['name'] . '.');
        break;
    default:
        flash('error', 'Unknown action.');
}
redirect(input('return') === 'dashboard' ? 'student/dashboard.php' : $back);
