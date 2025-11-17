<?php

/**
 * Tokens API
 * Exposes design tokens for the React admin and future clients.
 */

// Suppress errors and warnings to ensure clean JSON output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering to catch any unexpected output
ob_start();

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/feature-flags.php';
require_once __DIR__ . '/../classes/Page.php';

// Clear any output that may have been generated
ob_clean();

header('Content-Type: application/json');

if (!feature_flag('tokens_api')) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Endpoint disabled']);
    exit;
}

// Check authentication without redirecting (for API)
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$user = getCurrentUser();
$pageClass = new Page();
$page = $pageClass->getByUserId($user['id']);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'current';

$tokenConfig = require __DIR__ . '/../config/tokens.php';

if ($method === 'GET') {
    if ($action === 'history') {
        if (!$page) {
            echo json_encode(['success' => false, 'error' => 'Page not found']);
            exit;
        }

        $historyRows = fetchAll(
            "SELECT h.id, h.overrides, h.created_at, h.created_by, u.email 
             FROM page_token_history h 
             LEFT JOIN users u ON u.id = h.created_by 
             WHERE h.page_id = ? 
             ORDER BY h.created_at DESC 
             LIMIT 20",
            [$page['id']]
        );

        $history = array_map(function ($row) {
            return [
                'id' => (int) $row['id'],
                'overrides' => json_decode($row['overrides'], true) ?? [],
                'created_at' => $row['created_at'],
                'created_by' => $row['created_by'] ? (int) $row['created_by'] : null,
                'created_by_email' => $row['email'] ?? null
            ];
        }, $historyRows ?? []);

        echo json_encode([
            'success' => true,
            'history' => $history
        ]);
        exit;
    }

    $overrides = [];

    if ($page && !empty($page['token_overrides'])) {
        $decoded = json_decode($page['token_overrides'], true);
        if (is_array($decoded)) {
            $overrides = $decoded;
        }
    }

    $merged = mergeTokens($tokenConfig, $overrides);

    echo json_encode([
        'success' => true,
        'tokens' => $merged,
        'overrides' => $overrides
    ]);
    exit;
}

if ($method === 'POST') {
    if (!$page) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Page not found']);
        exit;
    }

    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true);

    if (!is_array($payload)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid token payload']);
        exit;
    }

    $postAction = $payload['action'] ?? 'save';

    if ($postAction === 'rollback') {
        $historyId = isset($payload['history_id']) ? (int) $payload['history_id'] : 0;
        if (!$historyId) {
            echo json_encode(['success' => false, 'error' => 'History ID required for rollback.']);
            exit;
        }

        $history = fetchOne(
            "SELECT overrides FROM page_token_history WHERE id = ? AND page_id = ?",
            [$historyId, $page['id']]
        );

        if (!$history) {
            echo json_encode(['success' => false, 'error' => 'History entry not found.']);
            exit;
        }

        $overridesJson = $history['overrides'];
        $result = $pageClass->update($page['id'], [
            'token_overrides' => $overridesJson
        ]);

        if ($result) {
            // Record rollback as new history entry
            executeQuery(
                "INSERT INTO page_token_history (page_id, overrides, created_by) VALUES (?, ?, ?)",
                [$page['id'], $overridesJson, $user['id']]
            );

            echo json_encode([
                'success' => true,
                'overrides' => json_decode($overridesJson, true) ?? []
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to restore token snapshot.']);
        }
        exit;
    }

    unset($payload['action']);

    $overrides = $payload;

    $encoded = json_encode($overrides);

    $result = $pageClass->update($page['id'], [
        'token_overrides' => $encoded
    ]);

    if ($result) {
        executeQuery(
            "INSERT INTO page_token_history (page_id, overrides, created_by) VALUES (?, ?, ?)",
            [$page['id'], $encoded, $user['id']]
        );

        // Keep most recent 20 entries per page
        executeQuery(
            "DELETE FROM page_token_history 
             WHERE page_id = ? 
             AND id NOT IN (
                SELECT id FROM (
                    SELECT id FROM page_token_history WHERE page_id = ? ORDER BY created_at DESC LIMIT 20
                ) as recent
             )",
            [$page['id'], $page['id']]
        );
    }

    echo json_encode([
        'success' => (bool) $result,
        'error' => $result ? null : 'Failed to save token overrides'
    ]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);

function mergeTokens(array $base, array $overrides): array {
    if (empty($overrides)) {
        return $base;
    }

    return array_replace_recursive($base, $overrides);
}

