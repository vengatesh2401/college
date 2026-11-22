<?php
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/queries.php';
$user = require_role('admin');
$scopeClubId = null; $base = 'admin'; $canApprove = true;
$pageTitle = 'Members'; $active = 'members';
include dirname(__DIR__) . '/includes/dash_header.php';
include dirname(__DIR__) . '/includes/dash/members_view.php';
include dirname(__DIR__) . '/includes/dash_footer.php';
