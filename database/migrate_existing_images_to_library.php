<?php
/**
 * Migration Script: Migrate Existing Images to Media Library
 * 
 * This script scans existing upload directories and creates media library entries
 * for images that were previously uploaded.
 * 
 * Usage: php database/migrate_existing_images_to_library.php
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/MediaLibrary.php';
require_once __DIR__ . '/../classes/Page.php';

$pdo = getDB();
$mediaLibrary = new MediaLibrary();
$page = new Page();

echo "Starting migration of existing images to media library...\n\n";

// Directories to scan
$uploadDirs = [
    'profiles' => UPLOAD_PROFILES,
    'backgrounds' => UPLOAD_BACKGROUNDS,
    'thumbnails' => UPLOAD_THUMBNAILS
];

$totalMigrated = 0;
$totalSkipped = 0;
$totalErrors = 0;

foreach ($uploadDirs as $type => $dir) {
    echo "Scanning $type directory: $dir\n";
    
    if (!is_dir($dir)) {
        echo "  Directory does not exist, skipping.\n\n";
        continue;
    }
    
    // Get all image files in directory
    $files = glob($dir . '/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
    $fileCount = count($files);
    
    echo "  Found $fileCount files\n";
    
    foreach ($files as $filePath) {
        $relativePath = str_replace(ROOT_PATH, '', $filePath);
        $filename = basename($filePath);
        
        // Determine owner (user_id) based on file type
        $userId = null;
        
        if ($type === 'profiles' || $type === 'backgrounds') {
            // For profile/background images, get owner from pages table
            $column = $type === 'profiles' ? 'profile_image' : 'background_image';
            $imageUrl = APP_URL . $relativePath;
            
            $pageData = fetchOne(
                "SELECT user_id FROM pages WHERE $column = ?",
                [$imageUrl]
            );
            
            if ($pageData) {
                $userId = (int)$pageData['user_id'];
            }
        } elseif ($type === 'thumbnails') {
            // For thumbnails, check widgets table
            // Thumbnails are stored in widget config_data JSON
            $allWidgets = fetchAll("SELECT page_id, config_data FROM widgets");
            
            foreach ($allWidgets as $widget) {
                $config = is_string($widget['config_data']) 
                    ? json_decode($widget['config_data'], true) 
                    : ($widget['config_data'] ?? []);
                
                $thumbnailUrl = $config['thumbnail_image'] ?? null;
                
                if ($thumbnailUrl && (strpos($thumbnailUrl, $relativePath) !== false || strpos($thumbnailUrl, $filename) !== false)) {
                    $pageData = fetchOne("SELECT user_id FROM pages WHERE id = ?", [$widget['page_id']]);
                    if ($pageData) {
                        $userId = (int)$pageData['user_id'];
                        break;
                    }
                }
            }
        }
        
        // Skip if we can't determine owner
        if (!$userId) {
            echo "  ⚠ Skipping $filename - cannot determine owner\n";
            $totalSkipped++;
            continue;
        }
        
        // Check if already in library
        $existing = fetchOne(
            "SELECT id FROM user_media WHERE user_id = ? AND file_path = ?",
            [$userId, $relativePath]
        );
        
        if ($existing) {
            echo "  ⊙ Skipping $filename - already in library\n";
            $totalSkipped++;
            continue;
        }
        
        // Get file info
        $fileSize = filesize($filePath);
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);
        
        // Migrate to library
        $result = $mediaLibrary->migrateExistingImage(
            $relativePath,
            $userId,
            [
                'filename' => $filename,
                'file_size' => $fileSize,
                'mime_type' => $mimeType
            ]
        );
        
        if ($result['success']) {
            echo "  ✓ Migrated $filename (user_id: $userId)\n";
            $totalMigrated++;
        } else {
            echo "  ✗ Failed to migrate $filename: " . ($result['error'] ?? 'Unknown error') . "\n";
            $totalErrors++;
        }
    }
    
    echo "\n";
}

echo "Migration complete!\n";
echo "  Total migrated: $totalMigrated\n";
echo "  Total skipped: $totalSkipped\n";
echo "  Total errors: $totalErrors\n";

