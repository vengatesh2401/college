<?php
require_once dirname(__DIR__) . '/includes/coord_boot.php';
require_once dirname(__DIR__) . '/includes/components.php';
$pageTitle = 'Applications'; $active = 'requests';
include dirname(__DIR__) . '/includes/dash_header.php';
include dirname(__DIR__) . '/includes/dash/requests_view.php';
include dirname(__DIR__) . '/includes/dash_footer.php';
