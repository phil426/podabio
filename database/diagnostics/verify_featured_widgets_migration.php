<?php
/**
 * Verify Featured Widget Migration Status
 * Checks if the migration columns exist
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

$pdo = getDB();

echo "<!DOCTYPE html><html><head><title>Migration Verification</title>";
echo "<style>body{font-family:monospace;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} table{border-collapse:collapse;width:100%;margin-top:20px;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#f2f2f2;}</style></head><body>";
echo "<h1>Featured Widget Migration Verification</h1>";

try {
    // Check if columns exist
    $columns = $pdo->query("SHOW COLUMNS FROM widgets WHERE Field IN ('is_featured', 'featured_effect')")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='info'><strong>Checking widgets table structure...</strong></div><br>";
    
    // Get all columns to see the full structure
    $allColumns = $pdo->query("SHOW COLUMNS FROM widgets")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>Column Name</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    $hasIsFeatured = false;
    $hasFeaturedEffect = false;
    
    foreach ($allColumns as $col) {
        $highlight = '';
        if ($col['Field'] === 'is_featured' || $col['Field'] === 'featured_effect') {
            $highlight = " style='background:#d4edda;'";
            if ($col['Field'] === 'is_featured') $hasIsFeatured = true;
            if ($col['Field'] === 'featured_effect') $hasFeaturedEffect = true;
        }
        
        echo "<tr$highlight>";
        echo "<td><strong>" . htmlspecialchars($col['Field']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($col['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br><br>";
    
    if ($hasIsFeatured && $hasFeaturedEffect) {
        echo "<div class='success'><strong>✅ Migration Complete!</strong></div>";
        echo "<div class='success'>Both columns exist:</div>";
        echo "<ul>";
        echo "<li>✅ <code>is_featured</code> column exists</li>";
        echo "<li>✅ <code>featured_effect</code> column exists</li>";
        echo "</ul>";
        
        // Check for index
        $indexes = $pdo->query("SHOW INDEX FROM widgets WHERE Key_name = 'idx_is_featured'")->fetchAll();
        if (!empty($indexes)) {
            echo "<div class='success'>✅ Index <code>idx_is_featured</code> exists</div>";
        } else {
            echo "<div class='info'>⚠️ Index <code>idx_is_featured</code> not found (optional)</div>";
        }
        
        echo "<br><div class='success'><strong>🎉 All featured widget features are now enabled!</strong></div>";
        
    } else {
        echo "<div class='error'><strong>❌ Migration Incomplete</strong></div>";
        echo "<ul>";
        echo "<li>" . ($hasIsFeatured ? "✅" : "❌") . " <code>is_featured</code> column</li>";
        echo "<li>" . ($hasFeaturedEffect ? "✅" : "❌") . " <code>featured_effect</code> column</li>";
        echo "</ul>";
        
        if (!$hasIsFeatured || !$hasFeaturedEffect) {
            echo "<br><div class='info'>Run the migration: <a href='migrate_add_featured_widgets.php'>migrate_add_featured_widgets.php</a></div>";
        }
    }
    
    // Check current featured widgets count
    if ($hasIsFeatured) {
        $featuredCount = $pdo->query("SELECT COUNT(*) as count FROM widgets WHERE is_featured = 1")->fetch()['count'];
        echo "<br><div class='info'><strong>Current Stats:</strong></div>";
        echo "<ul>";
        echo "<li>Featured widgets: <strong>$featuredCount</strong></li>";
        echo "</ul>";
    }
    
} catch (PDOException $e) {
    echo "<div class='error'><strong>❌ Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "<br><br><a href='../editor.php'>← Back to Editor</a>";
echo "</body></html>";

