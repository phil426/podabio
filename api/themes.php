<?php
/**
 * Themes API Endpoint
 * Returns theme data for the editor
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$themeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($themeId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid theme ID']);
    exit;
}

$theme = fetchOne("SELECT * FROM themes WHERE id = ? AND is_active = 1", [$themeId]);

if (!$theme) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Theme not found']);
    exit;
}

echo json_encode(['success' => true, 'theme' => $theme]);

