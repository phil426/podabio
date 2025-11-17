<?php
/**
 * Migration Script: Rename podcast_directories to social_icons
 * 
 * IMPORTANT: Access this file via your web browser once, then DELETE it immediately for security.
 * Example: https://yourdomain.com/database/migrate.php
 * 
 * Or run via command line: php database/migrate.php
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
        <h1>Database Migration</h1>
        <p>This script will rename the <code>podcast_directories</code> table to <code>social_icons</code>.</p>
        <p><strong>⚠️ Warning:</strong> Ensure you have a database backup before proceeding.</p>
        <form method='POST' style='margin-top: 20px;'>
            <input type='hidden' name='confirm' value='1'>
            <button type='submit' style='background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-size: 16px;'>
                Run Migration
            </button>
        </form>
        <p style='margin-top: 30px; color: #666; font-size: 12px;'>
            Or run via CLI: <code>php database/migrate.php</code>
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
        body { font-family: Arial, sans-serif; padding: 40px; max-width: 800px; margin: 0 auto; background: #f5f5f5; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 8px; margin: 10px 0; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 4px; }
        pre { background: #fff; padding: 15px; border-radius: 8px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Database Migration: podcast_directories → social_icons</h1>
    
<?php
try {
    $pdo = getDB();
    
    // Check if podcast_directories table exists
    $tableExists = $pdo->query("SHOW TABLES LIKE 'podcast_directories'")->rowCount() > 0;
    
    if (!$tableExists) {
        // Check if social_icons already exists (migration already run)
        $newTableExists = $pdo->query("SHOW TABLES LIKE 'social_icons'")->rowCount() > 0;
        if ($newTableExists) {
            echo "<div class='success'><strong>✓ Migration Already Completed</strong><br>";
            echo "The <code>social_icons</code> table already exists. Migration has been run previously.</div>";
            echo "<div class='info'><strong>⚠️ Important:</strong> Please delete this migration script file for security.</div>";
            exit;
        } else {
            echo "<div class='error'><strong>✗ Error</strong><br>";
            echo "The <code>podcast_directories</code> table not found and <code>social_icons</code> doesn't exist.<br>";
            echo "Please run the <code>database/schema.sql</code> file first to create the tables.</div>";
            exit;
        }
    }
    
    echo "<div class='info'><strong>Starting Migration...</strong></div>";
    
    // Get count before migration
    $count = $pdo->query("SELECT COUNT(*) as count FROM podcast_directories")->fetch()['count'];
    echo "<div class='info'>Found <strong>$count</strong> records to migrate</div>";
    
    // Begin transaction
    $pdo->beginTransaction();
    
    try {
        // Rename the table
        $pdo->exec("RENAME TABLE podcast_directories TO social_icons");
        
        // Update table comment
        $pdo->exec("ALTER TABLE social_icons COMMENT = 'Social icons and platform links for pages'");
        
        // Verify migration
        $verifyCount = $pdo->query("SELECT COUNT(*) as count FROM social_icons")->fetch()['count'];
        
        // Commit transaction
        $pdo->commit();
        
        echo "<div class='success'>";
        echo "<strong>✓ Migration Completed Successfully!</strong><br><br>";
        echo "✓ Table renamed: <code>podcast_directories</code> → <code>social_icons</code><br>";
        echo "✓ Records migrated: <strong>$verifyCount</strong> records<br>";
        echo "✓ Table comment updated<br>";
        echo "</div>";
        
        echo "<div class='info' style='margin-top: 20px;'>";
        echo "<strong>Next Steps:</strong><br>";
        echo "1. ✓ Test the editor - navigate to your editor page and verify 'Social Icons' tab works<br>";
        echo "2. ✓ Test adding a social icon<br>";
        echo "3. <strong style='color: #721c24;'>⚠️ DELETE this migration script file immediately for security!</strong><br>";
        echo "   Location: <code>" . __FILE__ . "</code>";
        echo "</div>";
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    echo "<div class='error'>";
    echo "<strong>✗ Migration Failed</strong><br><br>";
    echo "Error: " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "Error Code: " . $e->getCode() . "<br><br>";
    echo "Please check your database connection and try again.";
    echo "</div>";
}
?>
</body>
</html>

