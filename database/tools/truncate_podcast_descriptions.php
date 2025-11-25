<?php
/**
 * Truncate podcast_description to 140 characters
 * This script will update all pages with descriptions longer than 140 characters
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

$pdo = getDB();

echo "Starting truncation of podcast_description fields...\n\n";

try {
    // Find all pages with descriptions longer than 140 characters
    $stmt = $pdo->query("
        SELECT id, username, podcast_name, 
               LENGTH(podcast_description) as desc_length,
               LEFT(podcast_description, 50) as desc_preview
        FROM pages 
        WHERE podcast_description IS NOT NULL 
        AND LENGTH(podcast_description) > 140
    ");
    
    $pages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($pages)) {
        echo "✅ No pages found with descriptions longer than 140 characters.\n";
        exit(0);
    }
    
    echo "Found " . count($pages) . " page(s) with descriptions longer than 140 characters:\n\n";
    
    foreach ($pages as $page) {
        echo "  - ID: {$page['id']}, Username: {$page['username']}, Name: {$page['podcast_name']}\n";
        echo "    Length: {$page['desc_length']} characters\n";
        echo "    Preview: " . htmlspecialchars($page['desc_preview']) . "...\n\n";
    }
    
    echo "Truncating descriptions to 140 characters...\n\n";
    
    // Update all descriptions to be truncated to 140 characters
    $updateStmt = $pdo->prepare("
        UPDATE pages 
        SET podcast_description = CASE 
            WHEN LENGTH(podcast_description) > 140 
            THEN CONCAT(LEFT(podcast_description, 137), '...')
            ELSE podcast_description
        END
        WHERE podcast_description IS NOT NULL 
        AND LENGTH(podcast_description) > 140
    ");
    
    $updateStmt->execute();
    $affectedRows = $updateStmt->rowCount();
    
    echo "✅ Successfully truncated {$affectedRows} description(s) to 140 characters.\n\n";
    
    // Verify the update
    $verifyStmt = $pdo->query("
        SELECT COUNT(*) as count
        FROM pages 
        WHERE podcast_description IS NOT NULL 
        AND LENGTH(podcast_description) > 140
    ");
    $remaining = $verifyStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($remaining['count'] == 0) {
        echo "✅ Verification: All descriptions are now 140 characters or less.\n";
    } else {
        echo "⚠️  Warning: {$remaining['count']} description(s) are still longer than 140 characters.\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nDone!\n";










