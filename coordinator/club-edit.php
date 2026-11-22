<?php
require_once dirname(__DIR__) . '/includes/coord_boot.php';
$s = db()->prepare('SELECT * FROM clubs WHERE id=?'); $s->execute([$scopeClubId]);
$club = $s->fetch();
$isAdmin = false; $returnTo = 'coordinator/club-edit.php';
$pageTitle = 'Club information'; $active = 'club-edit';
include dirname(__DIR__) . '/includes/dash_header.php';
include dirname(__DIR__) . '/includes/dash/club_form.php';
include dirname(__DIR__) . '/includes/dash_footer.php';
