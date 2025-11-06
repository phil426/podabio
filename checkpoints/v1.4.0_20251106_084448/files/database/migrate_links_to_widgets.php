<?php
/**
 * Migration Script: Migrate links table to widgets table
 * 
 * IMPORTANT: Access this file via your web browser once, then DELETE it immediately for security.
 * Example: https://yourdomain.com/database/migrate_links_to_widgets.php?key=migrate_2024-01-01
 * 
 * Or run via command line: php database/migrate_links_to_widgets.php
 */

// Simple security check - remove this file after migration!
$migrationKey = isset($_GET['key']) ? $_GET['key'] : '';
$expectedKey = 'migrate_' . date('Y-m-d'); // Simple date-based key

// For command line execution
if (php_sapi_name() === 'cli') {
    $expectedKey = $migrationKey = 'cli'; // Allow CLI without key
}

if ($migrationKey !== $expectedKey && !isset($_POST['confirm'])) {
    die("
    <html>
    <head><title>Migration Required</title></head>
    <body style='font-family: Arial; padding: 40px; max-width: 600px; margin: 0 auto;'>
        <h1>Database Migration: Links → Widgets</h1>
        <p>This script will:</p>
        <ul>
            <li>Create the <code>widgets</code> table</li>
            <li>Migrate all data from <code>links</code> table to <code>widgets</code> table</li>
            <li>Convert link data structure to widget config_data JSON</li>
        </ul>
        <p><strong>⚠️ Warning:</strong> Ensure you have a database backup before proceeding.</p>
        <form method='POST' style='margin-top: 20px;'>
            <input type='hidden' name='confirm' value='1'>
            <button type='submit' style='background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-size: 16px;'>
                Run Migration
            </button>
        </form>
        <p style='margin-top: 30px; color: #666; font-size: 12px;'>
            Or run via CLI: <code>php database/migrate_links_to_widgets.php</code>
        </p>
    </body>
    </html>
    ");
}

require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Migration Results</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; max-width: 800px; margin: 0 auto; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; margin: 10px 0; border-radius: 5px; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>Migration: Links → Widgets</h1>
    
<?php
try {
    $pdo = getDB();
    
    // Check if widgets table already exists
    $widgetsTableExists = $pdo->query("SHOW TABLES LIKE 'widgets'")->rowCount() > 0;
    
    if ($widgetsTableExists) {
        $count = $pdo->query("SELECT COUNT(*) as count FROM widgets")->fetch()['count'];
        echo "<div class='success'><strong>✓ Widgets Table Already Exists</strong><br>";
        echo "The <code>widgets</code> table already exists with <strong>$count</strong> records.</div>";
        
        // Check if links table still has data
        $linksTableExists = $pdo->query("SHOW TABLES LIKE 'links'")->rowCount() > 0;
        if ($linksTableExists) {
            $linksCount = $pdo->query("SELECT COUNT(*) as count FROM links")->fetch()['count'];
            if ($linksCount > 0) {
                echo "<div class='info'><strong>ℹ️ Note:</strong> The <code>links</code> table still exists with <strong>$linksCount</strong> records.<br>";
                echo "Migration may need to be run again to move remaining data.</div>";
            }
        }
        echo "<div class='info'><strong>⚠️ Important:</strong> Please delete this migration script file for security.</div>";
        exit;
    }
    
    // Check if links table exists
    $linksTableExists = $pdo->query("SHOW TABLES LIKE 'links'")->rowCount() > 0;
    
    if (!$linksTableExists) {
        echo "<div class='error'><strong>✗ Error</strong><br>";
        echo "The <code>links</code> table not found.<br>";
        echo "Please ensure the database schema is up to date.</div>";
        exit;
    }
    
    echo "<div class='info'><strong>Starting Migration...</strong></div>";
    
    // Get count before migration
    $count = $pdo->query("SELECT COUNT(*) as count FROM links")->fetch()['count'];
    echo "<div class='info'>Found <strong>$count</strong> records to migrate</div>";
    
    // Begin transaction
    $pdo->beginTransaction();
    
    try {
        // Step 1: Create widgets table
        echo "<div class='info'>Creating <code>widgets</code> table...</div>";
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS widgets (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            page_id INT UNSIGNED NOT NULL,
            widget_type VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            config_data JSON NULL,
            display_order INT UNSIGNED DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
            INDEX idx_page_id (page_id),
            INDEX idx_display_order (display_order),
            INDEX idx_widget_type (widget_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        echo "<div class='success'>✓ Widgets table created</div>";
        
        // Step 2: Migrate data from links to widgets
        if ($count > 0) {
            echo "<div class='info'>Migrating data from <code>links</code> to <code>widgets</code>...</div>";
            
            $links = $pdo->query("SELECT * FROM links ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
            $migrated = 0;
            
            foreach ($links as $link) {
                // Convert link data to widget config_data JSON
                $configData = [
                    'url' => $link['url'] ?? '',
                    'thumbnail_image' => $link['thumbnail_image'] ?? null,
                    'icon' => $link['icon'] ?? null,
                    'disclosure_text' => $link['disclosure_text'] ?? null,
                    'original_type' => $link['type'] ?? 'custom'
                ];
                
                // Map old type to widget_type
                $oldType = $link['type'] ?? 'custom';
                $typeMap = [
                    'custom' => 'custom_link',
                    'youtube' => 'youtube_video',
                    'social' => 'custom_link',
                    'affiliate' => 'custom_link',
                    'amazon_affiliate' => 'custom_link',
                    'sponsor' => 'custom_link',
                    'email_subscribe' => 'custom_link',
                    'tiktok' => 'custom_link',
                    'instagram' => 'custom_link',
                    'shopify' => 'custom_link',
                    'spring' => 'custom_link',
                    'podcast_directory' => 'custom_link'
                ];
                $widgetType = $typeMap[$oldType] ?? 'custom_link';
                
                $stmt = $pdo->prepare("
                    INSERT INTO widgets (page_id, widget_type, title, config_data, display_order, is_active, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $link['page_id'],
                    $widgetType,
                    $link['title'] ?? 'Untitled Widget',
                    json_encode($configData),
                    $link['display_order'] ?? 0,
                    $link['is_active'] ?? 1,
                    $link['created_at'] ?? date('Y-m-d H:i:s'),
                    $link['updated_at'] ?? date('Y-m-d H:i:s')
                ]);
                
                $migrated++;
            }
            
            echo "<div class='success'>✓ Migrated <strong>$migrated</strong> records to widgets table</div>";
        }
        
        // Commit transaction
        $pdo->commit();
        
        echo "<div class='success'><strong>✓ Migration completed successfully!</strong></div>";
        echo "<div class='info'><strong>Next steps:</strong><br>";
        echo "1. Verify data: <code>SELECT COUNT(*) FROM widgets;</code><br>";
        echo "2. Test the editor to ensure widgets are working<br>";
        echo "3. Update code to use widgets table instead of links table<br>";
        echo "4. <strong>Delete this migration script file for security</strong></div>";
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    echo "<div class='error'><strong>✗ Migration Failed</strong><br>";
    echo "Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit(1);
}
?>

</body>
</html>

