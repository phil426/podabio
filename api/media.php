<?php
/**
 * Media Library API Endpoint
 * Handles user media library operations
 * GET /api/media.php - List user's media (with pagination)
 * POST /api/media.php - Upload new image to library
 * DELETE /api/media.php?id={media_id} - Delete media item
 * GET /api/media.php?migrate=true - Migrate existing images (admin/one-time use)
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../classes/MediaLibrary.php';
require_once __DIR__ . '/../classes/Page.php';

// Suppress any output before JSON
ob_start();

header('Content-Type: application/json');

// Require authentication
requireAuth();

$user = getCurrentUser();
$userId = $user['id'];
$mediaLibrary = new MediaLibrary();
$method = $_SERVER['REQUEST_METHOD'];

// Handle GET requests
if ($method === 'GET') {
    // Migration endpoint (one-time use, admin only)
    if (isset($_GET['migrate']) && $_GET['migrate'] === 'true') {
        // For now, this requires manual script execution
        // We'll create a separate migration script for this
        echo json_encode([
            'success' => false,
            'error' => 'Migration should be done via migration script, not API endpoint'
        ]);
        exit;
    }
    
    // Get single media item
    if (isset($_GET['id'])) {
        $mediaId = (int)$_GET['id'];
        $mediaItem = $mediaLibrary->getMediaItem($mediaId, $userId);
        
        if (!$mediaItem) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Media item not found']);
            exit;
        }
        
        echo json_encode(['success' => true, 'media' => $mediaItem]);
        exit;
    }
    
    // List user's media with pagination
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $perPage = isset($_GET['per_page']) ? max(1, (int)$_GET['per_page']) : MEDIA_PER_PAGE;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    
    $result = $mediaLibrary->getUserMedia($userId, [
        'page' => $page,
        'per_page' => $perPage,
        'search' => $search
    ]);
    
    echo json_encode($result);
    exit;
}

// Handle POST requests (upload)
if ($method === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit;
    }
    
    // Check if file was uploaded
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $error = 'No file uploaded';
        if (isset($_FILES['image']['error'])) {
            switch ($_FILES['image']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $error = 'File size exceeds maximum allowed size';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $error = 'File was only partially uploaded';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $error = 'No file was uploaded';
                    break;
                default:
                    $error = 'Upload error occurred (error code: ' . $_FILES['image']['error'] . ')';
            }
        }
        
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $error]);
        exit;
    }
    
    // Upload to media library
    $result = $mediaLibrary->uploadToLibrary($_FILES['image'], $userId);
    
    if (!$result['success']) {
        http_response_code(400);
        ob_clean(); // Clear any output before JSON
        echo json_encode($result);
        exit;
    }
    
    // Get full media item for response
    $mediaItem = $mediaLibrary->getMediaItem($result['media_id'], $userId);
    
    if (!$mediaItem) {
        http_response_code(500);
        ob_clean(); // Clear any output before JSON
        echo json_encode([
            'success' => false,
            'error' => 'Failed to retrieve uploaded media item'
        ]);
        exit;
    }
    
    ob_clean(); // Clear any output before JSON
    echo json_encode([
        'success' => true,
        'media' => $mediaItem,
        'message' => 'Image uploaded to media library successfully'
    ]);
    exit;
}

// Handle DELETE requests
if ($method === 'DELETE') {
    // Verify CSRF token (from query parameter, header, or POST data)
    $csrfToken = $_GET['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit;
    }
    
    $mediaId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$mediaId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Media ID required']);
        exit;
    }
    
    $result = $mediaLibrary->deleteMedia($mediaId, $userId);
    
    if (!$result['success']) {
        http_response_code(400);
        echo json_encode($result);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Media item deleted successfully'
    ]);
    exit;
}

// Method not allowed
http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);

