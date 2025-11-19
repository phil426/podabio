<?php
/**
 * Backward Compatibility Redirect
 * PodaBio - Redirects old react-admin.php to new userdashboard.php
 * 
 * This file exists for backward compatibility. All references should
 * be updated to use userdashboard.php instead.
 */

header('Location: /admin/userdashboard.php', true, 301);
exit;
