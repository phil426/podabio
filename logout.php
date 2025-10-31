<?php
/**
 * Logout
 * Podn.Bio
 */

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/classes/User.php';

$user = new User();
$user->logout();

redirect('/login.php');

