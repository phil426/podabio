<?php
/**
 * Editor.php - Legacy Admin Panel (ARCHIVED)
 * PodaBio
 * 
 * This file has been archived. All users are redirected to Lefty dashboard.
 * Original file moved to: archive/editor.php
 * 
 * Date Archived: 2025-01-XX
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

requireAuth();

// Redirect all traffic to Lefty dashboard
$_SESSION['admin_panel'] = 'lefty';
redirect('/admin/userdashboard.php');
exit;

