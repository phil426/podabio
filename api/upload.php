<?php
/**
 * Image Upload API Endpoint
 * Handles image uploads for pages
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../classes/Page.php';
require_once __DIR__ . '/../classes/ImageHandler.php';

// Require authentication
requireAuth();

// Set JSON response header
header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$user = getCurrentUser();
$userId = $user['id'];

// Get user's page
$page = new Page();
$userPage = $page->getByUserId($userId);

if (!$userPage) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Page not found']);
    exit;
}

$pageId = $userPage['id'];

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
    } else {
        $error = 'No file data received. $_FILES keys: ' . implode(', ', array_keys($_FILES));
    }
    
    // Log for debugging
    error_log('Upload failed: ' . $error . ' | POST keys: ' . implode(', ', array_keys($_POST)));
    
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $error]);
    exit;
}

$imageType = sanitizeInput($_POST['type'] ?? '');
$allowedTypes = ['profile', 'background', 'thumbnail'];

if (!in_array($imageType, $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid image type']);
    exit;
}

// Upload image
$imageHandler = new ImageHandler();
$result = $imageHandler->uploadImage($_FILES['image'], $imageType);

if (!$result['success']) {
    // Log error for debugging
    error_log('ImageHandler upload failed: ' . ($result['error'] ?? 'Unknown error'));
    http_response_code(400);
    echo json_encode($result);
    exit;
}

// Update page with image URL
$updateField = $imageType === 'profile' ? 'profile_image' : ($imageType === 'background' ? 'background_image' : null);

if ($updateField) {
    // Get old image path to delete later
    $oldImage = $userPage[$updateField] ?? null;
    
    // Update page
    $updateResult = $page->update($pageId, [$updateField => $result['url']]);
    
    if ($updateResult) {
        // Delete old image if exists
        if ($oldImage && strpos($oldImage, APP_URL) === 0) {
            $oldPath = str_replace(APP_URL, '', $oldImage);
            $imageHandler->deleteImage($oldPath);
        }
        
        error_log('Profile image updated successfully: ' . $result['url']);
        
        echo json_encode([
            'success' => true,
            'url' => $result['url'],
            'path' => $result['path'],
            'message' => ucfirst($imageType) . ' image uploaded successfully'
        ]);
    } else {
        // Delete uploaded file if page update failed
        $imageHandler->deleteImage($result['path']);
        error_log('Failed to update page with image. Page ID: ' . $pageId . ', Update field: ' . $updateField);
        echo json_encode(['success' => false, 'error' => 'Failed to update page with image']);
    }
} else {
    // For thumbnails, just return the URL (can be used in link forms)
    echo json_encode([
        'success' => true,
        'url' => $result['url'],
        'path' => $result['path']
    ]);
}

