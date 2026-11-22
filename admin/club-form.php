<?php
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/queries.php';
$user = require_role('admin');
$club = null;
if ($id = input_int('id')) {
    $s = db()->prepare('SELECT * FROM clubs WHERE id=?'); $s->execute([$id]);
    $club = $s->fetch();
    if (!$club) { flash('error', 'Club not found.'); redirect('admin/clubs.php'); }
}
$isAdmin = true; $returnTo = 'admin/clubs.php';
$pageTitle = $club ? 'Edit club' : 'Add club'; $active = 'clubs';
include dirname(__DIR__) . '/includes/dash_header.php';
include dirname(__DIR__) . '/includes/dash/club_form.php';
include dirname(__DIR__) . '/includes/dash_footer.php';
