<?php
/**
 * Themes API Endpoint
 * Returns theme data for the editor
 * GET /api/themes.php?id=X - Get single theme
 * GET /api/themes.php - Get all active themes
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Theme.php';

header('Content-Type: application/json');

$themeClass = new Theme();
$themeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If no ID provided, return list of all active themes
if ($themeId <= 0) {
    $themes = $themeClass->getAllThemes(true);
    echo json_encode([
        'success' => true,
        'themes' => $themes,
        'count' => count($themes)
    ]);
    exit;
}

// Get single theme with validation
$theme = $themeClass->getTheme($themeId);

if (!$theme) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Theme not found']);
    exit;
}

echo json_encode(['success' => true, 'theme' => $theme]);

