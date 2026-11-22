<?php
require_once dirname(__DIR__) . '/includes/auth.php';
// Logout via GET is acceptable here (low-risk), session is fully destroyed.
logout_user();
session_start();
flash('success', 'You have been signed out.');
redirect('index.php');
