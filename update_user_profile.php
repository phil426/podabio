<?php
/**
 * Update User Profile Image Script
 * Usage: php update_user_profile.php
 */

// Load database configuration
require_once __DIR__ . '/config/database.php';

// Disable error display for cleaner API output, but log errors
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/plain');

$email = 'thegetphily@gmail.com';
// Using a high-quality placeholder image
$placeholderImage = 'https://ui-avatars.com/api/?name=Phil&background=6366f1&color=fff&size=512&font-size=0.5';

try {
    // Manually create connection if getDBConnection is not available
    if (function_exists('getDBConnection')) {
        $pdo = getDBConnection();
    } else {
        // Fallback: create PDO instance directly
        // Assuming DB_HOST, DB_NAME, DB_USER, DB_PASS are defined in config/database.php
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    // 1. Find the user ID
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die("Error: User with email '$email' not found.\n");
    }

    $userId = $user['id'];
    echo "Found User ID: " . $userId . "\n";

    // 2. Find the page ID for this user
    $stmt = $pdo->prepare("SELECT id, profile_image FROM pages WHERE user_id = ?");
    $stmt->execute([$userId]);
    $page = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$page) {
        die("Error: No page found for user ID $userId.\n");
    }

    echo "Found Page ID: " . $page['id'] . "\n";
    echo "Current Profile Image: " . ($page['profile_image'] ?? 'NULL') . "\n";

    // 3. Update the profile image
    $updateStmt = $pdo->prepare("UPDATE pages SET profile_image = ? WHERE id = ?");
    $result = $updateStmt->execute([$placeholderImage, $page['id']]);

    if ($result) {
        echo "✅ Success: Profile image updated to placeholder.\n";
        echo "New Image URL: $placeholderImage\n";
    } else {
        echo "❌ Error: Failed to update database record.\n";
    }

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage() . "\n");
}
