<?php
/**
 * Classic Admin Panel Entry Point
 * PodaBio - Traditional PHP-based admin interface
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/security.php';

requireAuth();

// Store panel preference in session
$_SESSION['admin_panel'] = 'classic';

// Redirect to the main admin dashboard
redirect('/admin/index.php');
exit;

