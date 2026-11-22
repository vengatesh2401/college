<?php
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/queries.php';
$user = require_role('admin');
$scopeClubId = null; $base = 'admin';
$pageTitle = input_int('id') ? 'Edit event' : 'New event'; $active = 'events';
include dirname(__DIR__) . '/includes/dash_header.php';
include dirname(__DIR__) . '/includes/dash/event_form.php';
include dirname(__DIR__) . '/includes/dash_footer.php';
