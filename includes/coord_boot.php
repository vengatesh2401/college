<?php
/** Boot for coordinator pages: sets $user, $club, $scopeClubId, $base, $canApprove. */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/queries.php';
require_once __DIR__ . '/components.php';
$user = require_role('coordinator');
$club = coordinator_club((int)$user['id']);
$base = 'coordinator';
if (!$club) {
    $pageTitle = 'No club assigned'; $active = 'index';
    include __DIR__ . '/dash_header.php';
    echo empty_state('people-group', 'No club assigned yet', 'Ask the administrator to assign your account to a club.');
    include __DIR__ . '/dash_footer.php';
    exit;
}
$scopeClubId = (int)$club['id'];
$canApprove = (bool)$club['coordinator_can_approve'];
