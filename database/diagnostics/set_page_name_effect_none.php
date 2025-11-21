<?php
/**
 * Set Page Name Effect to None
 * Quick script to set page_name_effect to NULL (none)
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../classes/Page.php';

// Require authentication
requireAuth();

$user = getCurrentUser();
$userId = $user['id'];

// Get user's page
$pageClass = new Page();
$page = $pageClass->getByUserId($userId);

if (!$page) {
    die('Error: No page found for this user.');
}

$pageId = $page['id'];

// Update page_name_effect to NULL (none)
$result = $pageClass->update($pageId, ['page_name_effect' => null]);

if ($result) {
    echo "✅ Success! Page Name Effect set to 'None' for page ID: {$pageId}<br>";
    echo "Current effect value: " . var_export($page['page_name_effect'], true) . " → NULL<br>";
    echo "<br>";
    echo "You can now refresh your editor or public page to see the change.<br>";
    echo "<br>";
    echo "<a href='/editor.php'>← Back to Editor</a>";
} else {
    echo "❌ Error: Failed to update page_name_effect.<br>";
    echo "Please check error logs for details.";
}

