<?php
/**
 * Widget Click Tracker
 * Podn.Bio - Tracks clicks on widgets and redirects
 * Note: Uses "links" table for backend compatibility, but tracks widget clicks
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/classes/Analytics.php';

$linkId = (int)($_GET['link_id'] ?? 0);
$pageId = (int)($_GET['page_id'] ?? 0);

if (!$linkId || !$pageId) {
    http_response_code(404);
    die('Invalid link');
}

// Get link
$link = fetchOne("SELECT * FROM links WHERE id = ? AND page_id = ? AND is_active = 1", [$linkId, $pageId]);

if (!$link) {
    http_response_code(404);
    die('Link not found');
}

// Track click
$analytics = new Analytics();
$analytics->trackClick($pageId, $linkId);

// Redirect to link URL
redirect($link['url']);

