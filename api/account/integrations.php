<?php
/**
 * Account Integrations API
 */

// Suppress errors and warnings to ensure clean JSON output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering to catch any unexpected output
ob_start();

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Clear any output that may have been generated
ob_clean();

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$userId = getUserId();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get_status':
        // Get connection status for all integrations
        $user = fetchOne(
            'SELECT instagram_user_id, instagram_access_token, instagram_token_expires_at FROM users WHERE id = ?',
            [$userId]
        );
        
        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'User not found']);
            exit;
        }
        
        $hasInstagram = !empty($user['instagram_user_id']) && !empty($user['instagram_access_token']);
        $instagramExpired = false;
        
        if ($hasInstagram && !empty($user['instagram_token_expires_at'])) {
            $expiresAt = strtotime($user['instagram_token_expires_at']);
            $instagramExpired = $expiresAt < time();
        }
        
        // Load meta.php first (contains Facebook/Instagram credentials)
        try {
            require_once __DIR__ . '/../../config/meta.php';
            require_once __DIR__ . '/../../config/instagram.php';
            
            // Check if Instagram is configured
            $instagramConfigured = !empty(INSTAGRAM_APP_ID) && !empty(INSTAGRAM_APP_SECRET);
            
            // Generate OAuth URL (may return empty string if not configured)
            $instagramLinkUrl = '';
            if ($instagramConfigured && function_exists('getInstagramAuthUrl')) {
                try {
                    $instagramLinkUrl = getInstagramAuthUrl('link');
                } catch (Exception $e) {
                    error_log("Failed to generate Instagram OAuth URL: " . $e->getMessage());
                    $instagramLinkUrl = '';
                }
            }
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'instagram' => [
                        'connected' => $hasInstagram && !$instagramExpired,
                        'expired' => $instagramExpired,
                        'link_url' => $instagramLinkUrl,
                        'configured' => $instagramConfigured
                    ]
                ]
            ]);
        } catch (Exception $e) {
            error_log("Instagram integration error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to load Instagram configuration: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'disconnect_instagram':
        try {
            executeQuery(
                "UPDATE users SET 
                    instagram_user_id = NULL,
                    instagram_access_token = NULL,
                    instagram_token_expires_at = NULL
                WHERE id = ?",
                [$userId]
            );
            
            echo json_encode([
                'success' => true,
                'message' => 'Instagram disconnected successfully'
            ]);
        } catch (PDOException $e) {
            error_log("Instagram disconnect failed: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to disconnect Instagram'
            ]);
        }
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        break;
}

