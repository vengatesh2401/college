<?php
require_once dirname(__DIR__) . '/includes/coord_boot.php';
$pageTitle = input_int('id') ? 'Edit event' : 'New event'; $active = 'events';
include dirname(__DIR__) . '/includes/dash_header.php';
include dirname(__DIR__) . '/includes/dash/event_form.php';
include dirname(__DIR__) . '/includes/dash_footer.php';
