<?php
/**
 * Themes API Endpoint
 * Supports browsing system themes and managing user themes.
 * GET /api/themes.php?scope=all - Get system + user themes
 * GET /api/themes.php?id=X - Get single theme
 * POST actions: clone, delete, rename
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/Theme.php';

header('Content-Type: application/json');

requireAuth();

$user = getCurrentUser();
$userId = $user['id'];
$themeClass = new Theme();
$method = $_SERVER['REQUEST_METHOD'];
$themeId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($method === 'GET') {
    if ($themeId > 0) {
        $theme = $themeClass->getTheme($themeId);

        if (!$theme) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Theme not found']);
            exit;
        }

        echo json_encode(['success' => true, 'theme' => $theme]);
        exit;
    }

    $scope = $_GET['scope'] ?? 'system';

    if ($scope === 'user') {
        $userThemes = $themeClass->getUserThemes($userId);
        echo json_encode(['success' => true, 'themes' => array_values($userThemes)]);
        exit;
    }

    if ($scope === 'all') {
        $systemThemes = $themeClass->getSystemThemes(true);
        $userThemes = $themeClass->getUserThemes($userId);
        echo json_encode([
            'success' => true,
            'system' => array_values($systemThemes),
            'user' => array_values($userThemes)
        ]);
        exit;
    }

    $themes = $themeClass->getAllThemes(true);
    echo json_encode([
        'success' => true,
        'themes' => $themes,
        'count' => count($themes)
    ]);
    exit;
}

if ($method === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'clone':
            $sourceId = (int) ($_POST['theme_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');

            if (!$sourceId) {
                echo json_encode(['success' => false, 'theme_id' => null, 'error' => 'Theme ID required to clone.']);
                break;
            }

            $result = $themeClass->cloneTheme($sourceId, $userId, $name ?: null);
            echo json_encode($result);
            break;

        case 'delete':
            $deleteId = (int) ($_POST['theme_id'] ?? 0);
            if (!$deleteId) {
                echo json_encode(['success' => false, 'error' => 'Theme ID required to delete.']);
                break;
            }

            $success = $themeClass->deleteUserTheme($deleteId, $userId);
            echo json_encode(['success' => $success, 'error' => $success ? null : 'Unable to delete theme.']);
            break;

        case 'rename':
            $renameId = (int) ($_POST['theme_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');

            if (!$renameId) {
                echo json_encode(['success' => false, 'error' => 'Theme ID required to rename.']);
                break;
            }

            if ($name === '') {
                echo json_encode(['success' => false, 'error' => 'Theme name cannot be empty.']);
                break;
            }

            $success = $themeClass->updateUserTheme($renameId, $userId, $name);
            echo json_encode(['success' => $success, 'error' => $success ? null : 'Unable to rename theme.']);
            break;

        case 'create':
            $name = trim($_POST['name'] ?? '');
            $themeData = [];
            
            // Parse JSON theme data if provided
            if (isset($_POST['theme_data']) && is_string($_POST['theme_data'])) {
                $decoded = json_decode($_POST['theme_data'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $themeData = $decoded;
                }
            } else {
                // Fallback: parse individual fields
                if (isset($_POST['color_tokens'])) {
                    $themeData['color_tokens'] = json_decode($_POST['color_tokens'], true);
                }
                if (isset($_POST['typography_tokens'])) {
                    $themeData['typography_tokens'] = json_decode($_POST['typography_tokens'], true);
                }
                if (isset($_POST['spacing_tokens'])) {
                    $themeData['spacing_tokens'] = json_decode($_POST['spacing_tokens'], true);
                }
                if (isset($_POST['shape_tokens'])) {
                    $themeData['shape_tokens'] = json_decode($_POST['shape_tokens'], true);
                }
                if (isset($_POST['page_background'])) {
                    $themeData['page_background'] = $_POST['page_background'];
                }
            }

            if ($name === '') {
                echo json_encode(['success' => false, 'error' => 'Theme name is required.']);
                break;
            }

            $result = $themeClass->createTheme($userId, $name, $themeData);
            echo json_encode($result);
            break;

        case 'update':
            $updateId = (int) ($_POST['theme_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $themeData = [];
            
            // Parse JSON theme data if provided
            if (isset($_POST['theme_data']) && is_string($_POST['theme_data'])) {
                $decoded = json_decode($_POST['theme_data'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $themeData = $decoded;
                }
            } else {
                // Fallback: parse individual fields
                if (isset($_POST['color_tokens'])) {
                    $themeData['color_tokens'] = json_decode($_POST['color_tokens'], true);
                }
                if (isset($_POST['typography_tokens'])) {
                    $themeData['typography_tokens'] = json_decode($_POST['typography_tokens'], true);
                }
                if (isset($_POST['spacing_tokens'])) {
                    $themeData['spacing_tokens'] = json_decode($_POST['spacing_tokens'], true);
                }
                if (isset($_POST['shape_tokens'])) {
                    $themeData['shape_tokens'] = json_decode($_POST['shape_tokens'], true);
                }
                if (isset($_POST['page_background'])) {
                    $themeData['page_background'] = $_POST['page_background'];
                }
            }

            if (!$updateId) {
                echo json_encode(['success' => false, 'error' => 'Theme ID required to update.']);
                break;
            }

            if ($name === '') {
                echo json_encode(['success' => false, 'error' => 'Theme name is required.']);
                break;
            }

            $success = $themeClass->updateUserTheme($updateId, $userId, $name, $themeData);
            echo json_encode(['success' => $success, 'error' => $success ? null : 'Unable to update theme.']);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Unsupported theme action.']);
            break;
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);

