<?php
/**
 * Script to manually set page_name_effect to 'slashed' for testing
 * Usage: Visit this file in browser or run via CLI
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/session.php';

$pdo = getDB();

echo "<!DOCTYPE html><html><head><title>Set Page Name Effect to Slashed</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#1a1a1a;color:#0f0;} ";
echo ".success{color:#0f0;} .error{color:#f00;} .info{color:#0ff;} .warning{color:#ff0;} ";
echo "</style></head><body>";
echo "<h1>Set Page Name Effect to 'slashed'</h1>";

// Session is already started by includes/session.php
$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    echo "<div class='error'>✗ No user session found. Please log in first.</div>";
    echo "<div class='info'>Alternatively, edit this script to hardcode a page_id or user_id</div>";
    exit;
}

echo "<div class='info'>User ID from session: {$userId}</div>";

// First check if column exists
try {
    $columns = $pdo->query("SHOW COLUMNS FROM pages LIKE 'page_name_effect'")->fetchAll();
    if (empty($columns)) {
        echo "<div class='warning'>⚠ Column 'page_name_effect' does NOT exist.</div>";
        echo "<div class='info'>→ Please run the migration first: <a href='migrate_add_page_name_effect.php' style='color:#0ff;'>database/migrate_add_page_name_effect.php</a></div>";
        echo "<div class='info'>→ Or visit: <a href='/database/migrate_add_page_name_effect.php' style='color:#0ff;'>/database/migrate_add_page_name_effect.php</a></div>";
        exit;
    }
    echo "<div class='success'>✓ Column 'page_name_effect' exists</div>";
} catch (PDOException $e) {
    echo "<div class='error'>✗ Error checking column: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit;
}

// Get page for this user
$page = $pdo->prepare("SELECT id, username, podcast_name FROM pages WHERE user_id = ? LIMIT 1");
$page->execute([$userId]);
$pageData = $page->fetch();

if (!$pageData) {
    echo "<div class='error'>✗ No page found for user ID {$userId}</div>";
    exit;
}

$pageId = $pageData['id'];
echo "<div class='info'>Page ID: {$pageId}, Username: {$pageData['username']}</div>";

// Get the current page_name_effect value
$currentEffect = $pdo->prepare("SELECT page_name_effect FROM pages WHERE id = ?");
$currentEffect->execute([$pageId]);
$currentData = $currentEffect->fetch();
echo "<div class='info'>Current page_name_effect: " . ($currentData['page_name_effect'] ?? 'NULL') . "</div>";

// Set to 'slashed'
try {
    $update = $pdo->prepare("UPDATE pages SET page_name_effect = ? WHERE id = ?");
    $update->execute(['slashed', $pageId]);
    echo "<div class='success'>✓ Updated page_name_effect to 'slashed' for page ID {$pageId}</div>";
    
    // Verify
    $verify = $pdo->prepare("SELECT page_name_effect FROM pages WHERE id = ?");
    $verify->execute([$pageId]);
    $result = $verify->fetch();
    echo "<div class='success'>✓ Verified value in database: " . ($result['page_name_effect'] ?? 'NULL') . "</div>";
    echo "<div class='info'>→ Now check editor.php?tab=appearance - the 'slashed' option should be selected</div>";
    echo "<div class='info'>→ Also check your public page - it should display the slashed effect</div>";
} catch (PDOException $e) {
    echo "<div class='error'>✗ Error updating: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</body></html>";
