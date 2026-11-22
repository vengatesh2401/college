<?php
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/components.php';
$u = require_role('student');
if (is_post()) {
    csrf_check();
    db()->prepare('UPDATE notifications SET is_read=1 WHERE user_id=?')->execute([$u['id']]);
    redirect('student/notifications.php');
}
$s = db()->prepare('SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC, id DESC LIMIT 50');
$s->execute([$u['id']]);
$list = $s->fetchAll();
$unread = unread_count((int)$u['id']);
$pageTitle = 'Notifications';
include dirname(__DIR__) . '/includes/header.php';
?>
<section class="page-head"><div class="container"><h1>Notifications</h1>
  <?php if ($unread): ?><form method="post"><?= csrf_field() ?><button class="btn btn-ghost btn-sm">Mark all as read</button></form><?php endif; ?></div></section>
<div class="container narrow">
  <div class="stack">
  <?php foreach ($list as $n): ?>
    <a class="notif <?= $n['is_read'] ? '' : 'unread' ?>" href="<?= e($n['link'] ? url($n['link']) : '#') ?>"><i class="fa-solid fa-bell"></i><div><b><?= e($n['title']) ?></b><p class="muted"><?= e($n['message']) ?></p></div><small class="muted"><?= e(time_ago($n['created_at'])) ?></small></a>
  <?php endforeach; if (!$list) echo empty_state('bell-slash', 'Nothing here yet', 'We will notify you about requests and events.'); ?>
  </div>
</div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
