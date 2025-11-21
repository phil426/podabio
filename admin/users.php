<?php
/**
 * Admin Panel - User Management (DEPRECATED)
 * PodaBio - Forces redirect to Lefty dashboard
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';

// FORCE REDIRECT TO LEFTY - This old admin panel is retired
$_SESSION['admin_panel'] = 'lefty';
redirect('/admin/userdashboard.php');
exit;
