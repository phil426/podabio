<?php
/**
 * Account Profile API
 * Returns basic account details for the Studio top bar.
 * Supports GET to fetch profile and POST to update profile.
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../classes/User.php';
require_once __DIR__ . '/../../classes/Subscription.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$userId = getUserId();
$userClass = new User();
$user = $userClass->getById($userId);

if (!$user) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}

// Handle POST request for updating profile
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw ?? '', true);
    
    if (!is_array($payload)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid request payload']);
        exit;
    }
    
    $updateData = [];
    
    // Handle display name (store in first_name)
    if (isset($payload['name'])) {
        $name = trim($payload['name']);
        if (strlen($name) > 100) {
            echo json_encode(['success' => false, 'error' => 'Display name must be 100 characters or less']);
            exit;
        }
        // Split name into first and last name
        $nameParts = preg_split('/\s+/', $name, 2);
        $updateData['first_name'] = $nameParts[0] ?? '';
        $updateData['last_name'] = $nameParts[1] ?? null;
    }
    
    // Handle email update
    if (isset($payload['email'])) {
        $email = trim($payload['email']);
        if (!isValidEmail($email)) {
            echo json_encode(['success' => false, 'error' => 'Invalid email address']);
            exit;
        }
        
        // Check if email is already taken by another user
        $existing = fetchOne("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $userId]);
        if ($existing) {
            echo json_encode(['success' => false, 'error' => 'Email address is already in use']);
            exit;
        }
        
        $updateData['email'] = $email;
    }
    
    if (empty($updateData)) {
        echo json_encode(['success' => false, 'error' => 'No fields to update']);
        exit;
    }
    
    $result = $userClass->update($userId, $updateData);
    
    if ($result) {
        // Update session email if email was changed
        if (isset($updateData['email'])) {
            $_SESSION['user_email'] = $updateData['email'];
        }
        
        // Fetch updated user data
        $updatedUser = $userClass->getById($userId);
        $subscriptionClass = new Subscription();
        $activeSubscription = $subscriptionClass->getActive($userId);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'email' => $updatedUser['email'],
                'name' => trim(($updatedUser['first_name'] ?? '') . ' ' . ($updatedUser['last_name'] ?? '')) ?: ($updatedUser['email'] ?? ''),
                'plan' => $activeSubscription['plan_type'] ?? 'free',
                'avatar_url' => $updatedUser['avatar_url'] ?? null
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update profile']);
    }
    exit;
}

// Handle GET request (default behavior)
$subscriptionClass = new Subscription();
$activeSubscription = $subscriptionClass->getActive($userId);

echo json_encode([
    'success' => true,
    'data' => [
        'email' => $user['email'],
        'name' => trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: ($user['email'] ?? ''),
        'plan' => $activeSubscription['plan_type'] ?? 'free',
        'avatar_url' => $user['avatar_url'] ?? null
    ]
]);

