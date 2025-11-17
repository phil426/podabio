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

// Suppress any output before JSON (warnings, notices, etc.)
ob_start();

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
                    // DEBUG: Log what we received
                    error_log("THEME API CREATE: Parsed theme_data JSON, widget_background=" . ($decoded['widget_background'] ?? 'NOT SET') . " (type: " . gettype($decoded['widget_background'] ?? null) . ")");
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
                if (isset($_POST['widget_background'])) {
                    $themeData['widget_background'] = $_POST['widget_background'];
                }
                if (isset($_POST['widget_border_color'])) {
                    $themeData['widget_border_color'] = $_POST['widget_border_color'];
                }
                if (isset($_POST['page_primary_font'])) {
                    $themeData['page_primary_font'] = $_POST['page_primary_font'];
                }
                if (isset($_POST['page_secondary_font'])) {
                    $themeData['page_secondary_font'] = $_POST['page_secondary_font'];
                }
                if (isset($_POST['widget_primary_font'])) {
                    $themeData['widget_primary_font'] = $_POST['widget_primary_font'];
                }
                if (isset($_POST['widget_secondary_font'])) {
                    $themeData['widget_secondary_font'] = $_POST['widget_secondary_font'];
                }
            }
            
            // DEBUG: Log what we're passing to createTheme
            error_log("THEME API CREATE: About to call createTheme with widget_background=" . ($themeData['widget_background'] ?? 'NOT SET') . " (type: " . gettype($themeData['widget_background'] ?? null) . ")");

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
                    // DEBUG: Log what we received
                    error_log("THEME API UPDATE: Parsed theme_data JSON, widget_background=" . ($decoded['widget_background'] ?? 'NOT SET') . " (type: " . gettype($decoded['widget_background'] ?? null) . ")");
                    // CRITICAL DEBUG: Log widget_styles for glow
                    error_log("THEME API UPDATE: widget_styles=" . json_encode($decoded['widget_styles'] ?? null));
                    if (isset($decoded['widget_styles']['border_effect'])) {
                        error_log("THEME API UPDATE: widget_styles.border_effect=" . $decoded['widget_styles']['border_effect']);
                        error_log("THEME API UPDATE: widget_styles.border_glow_intensity=" . ($decoded['widget_styles']['border_glow_intensity'] ?? 'NOT SET'));
                        error_log("THEME API UPDATE: widget_styles.glow_color=" . ($decoded['widget_styles']['glow_color'] ?? 'NOT SET'));
                    }
                    // CRITICAL DEBUG: Log shape_tokens
                    error_log("THEME API UPDATE: shape_tokens=" . json_encode($decoded['shape_tokens'] ?? null));
                    if (isset($decoded['shape_tokens']['corner'])) {
                        error_log("THEME API UPDATE: shape_tokens.corner=" . json_encode($decoded['shape_tokens']['corner']));
                    }
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
                if (isset($_POST['widget_background'])) {
                    $themeData['widget_background'] = $_POST['widget_background'];
                }
                if (isset($_POST['widget_border_color'])) {
                    $themeData['widget_border_color'] = $_POST['widget_border_color'];
                }
                if (isset($_POST['page_primary_font'])) {
                    $themeData['page_primary_font'] = $_POST['page_primary_font'];
                }
                if (isset($_POST['page_secondary_font'])) {
                    $themeData['page_secondary_font'] = $_POST['page_secondary_font'];
                }
                if (isset($_POST['widget_primary_font'])) {
                    $themeData['widget_primary_font'] = $_POST['widget_primary_font'];
                }
                if (isset($_POST['widget_secondary_font'])) {
                    $themeData['widget_secondary_font'] = $_POST['widget_secondary_font'];
                }
            }
            
            // DEBUG: Log what we're passing to updateUserTheme
            error_log("THEME API UPDATE: About to call updateUserTheme with widget_background=" . ($themeData['widget_background'] ?? 'NOT SET') . " (type: " . gettype($themeData['widget_background'] ?? null) . ")");

            if (!$updateId) {
                echo json_encode(['success' => false, 'error' => 'Theme ID required to update.']);
                break;
            }

            // DEBUG: Log theme update
            $hasTypographyTokens = isset($themeData['typography_tokens']);
            $typographyScale = null;
            if ($hasTypographyTokens && is_array($themeData['typography_tokens'])) {
                $typographyScale = $themeData['typography_tokens']['scale'] ?? null;
            }
            error_log("THEME UPDATE DEBUG: theme_id={$updateId}, userId={$userId}, has_typography_tokens=" . ($hasTypographyTokens ? 'yes' : 'no') . ", scale_xl=" . ($typographyScale['xl'] ?? 'null') . ", scale_sm=" . ($typographyScale['sm'] ?? 'null'));

            // Allow updates without name change (name can be empty if only theme_data is being updated)
            // Only require name if it's explicitly provided and not empty
            $nameToUse = $name !== '' ? $name : null;

            $success = $themeClass->updateUserTheme($updateId, $userId, $nameToUse, $themeData);
            
            // Clear theme cache after update to ensure fresh data on next load
            if ($success) {
                Theme::clearCache($updateId);
                error_log("THEME UPDATE DEBUG: Theme {$updateId} updated successfully, cache cleared");
            } else {
                error_log("THEME UPDATE DEBUG: Theme {$updateId} update failed");
            }
            // Clear any output buffer before sending JSON
            ob_clean();
            echo json_encode(['success' => $success, 'error' => $success ? null : 'Unable to update theme.']);
            break;

        default:
            http_response_code(400);
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'Unsupported theme action.']);
            break;
    }
    // Discard any buffered output
    ob_end_flush();
    exit;
}

http_response_code(405);
ob_clean();
echo json_encode(['success' => false, 'error' => 'Method not allowed']);
ob_end_flush();

