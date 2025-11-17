<?php
/**
 * Database Migration Status Checker
 * Run this file in your browser to check if migration was successful
 * URL: https://getphily.com/database/check_migration.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

header('Content-Type: text/html; charset=utf-8');

$pdo = getDB();

// Check themes table
$themesColumns = [];
$themesQuery = "SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                  AND TABLE_NAME = 'themes' 
                  AND COLUMN_NAME IN ('user_id', 'page_background', 'widget_styles', 'spatial_effect')
                ORDER BY COLUMN_NAME";
$themesStmt = $pdo->query($themesQuery);
$themesColumns = $themesStmt->fetchAll(PDO::FETCH_ASSOC);

// Check pages table
$pagesColumns = [];
$pagesQuery = "SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT 
               FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = DATABASE() 
                 AND TABLE_NAME = 'pages' 
                 AND COLUMN_NAME IN ('page_background', 'widget_styles', 'spatial_effect')
               ORDER BY COLUMN_NAME";
$pagesStmt = $pdo->query($pagesQuery);
$pagesColumns = $pagesStmt->fetchAll(PDO::FETCH_ASSOC);

// Check index
$indexExists = false;
try {
    $indexQuery = "SHOW INDEX FROM themes WHERE Key_name = 'idx_user_id'";
    $indexStmt = $pdo->query($indexQuery);
    $indexExists = $indexStmt->rowCount() > 0;
} catch (PDOException $e) {
    $indexExists = false;
}

// Expected columns
$expectedThemesColumns = ['user_id', 'page_background', 'widget_styles', 'spatial_effect'];
$expectedPagesColumns = ['page_background', 'widget_styles', 'spatial_effect'];

$themesFound = array_column($themesColumns, 'COLUMN_NAME');
$pagesFound = array_column($pagesColumns, 'COLUMN_NAME');

$themesMissing = array_diff($expectedThemesColumns, $themesFound);
$pagesMissing = array_diff($expectedPagesColumns, $pagesFound);

// Determine status
$migrationComplete = empty($themesMissing) && empty($pagesMissing) && $indexExists;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migration Status</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 900px;
            margin: 2rem auto;
            padding: 1rem;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            margin-top: 0;
            color: #333;
        }
        .status {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 2rem;
            font-weight: 600;
        }
        .status.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .section {
            margin: 2rem 0;
        }
        .section h2 {
            color: #555;
            border-bottom: 2px solid #ddd;
            padding-bottom: 0.5rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .check {
            color: #28a745;
            font-weight: bold;
        }
        .missing {
            color: #dc3545;
            font-weight: bold;
        }
        .summary {
            background: #e9ecef;
            padding: 1rem;
            border-radius: 6px;
            margin-top: 1rem;
        }
        .summary-item {
            margin: 0.5rem 0;
        }
        code {
            background: #f4f4f4;
            padding: 0.2rem 0.4rem;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Database Migration Status Check</h1>
        
        <?php if ($migrationComplete): ?>
            <div class="status success">
                ✅ <strong>Migration Complete!</strong> All required columns and indexes are present.
            </div>
        <?php else: ?>
            <div class="status error">
                ⚠️ <strong>Migration Incomplete</strong> Some columns or indexes are missing.
            </div>
        <?php endif; ?>
        
        <div class="section">
            <h2>Themes Table</h2>
            <table>
                <thead>
                    <tr>
                        <th>Column Name</th>
                        <th>Status</th>
                        <th>Data Type</th>
                        <th>Nullable</th>
                        <th>Default</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expectedThemesColumns as $col): ?>
                        <?php 
                        $found = in_array($col, $themesFound);
                        $colData = null;
                        if ($found) {
                            foreach ($themesColumns as $tc) {
                                if ($tc['COLUMN_NAME'] === $col) {
                                    $colData = $tc;
                                    break;
                                }
                            }
                        }
                        ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($col); ?></code></td>
                            <td>
                                <?php if ($found): ?>
                                    <span class="check">✅ Found</span>
                                <?php else: ?>
                                    <span class="missing">❌ Missing</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $found ? htmlspecialchars($colData['DATA_TYPE']) : '-'; ?></td>
                            <td><?php echo $found ? htmlspecialchars($colData['IS_NULLABLE']) : '-'; ?></td>
                            <td><?php echo $found ? htmlspecialchars($colData['COLUMN_DEFAULT'] ?? 'NULL') : '-'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="summary">
                <strong>Summary:</strong>
                <div class="summary-item">
                    Found: <strong><?php echo count($themesFound); ?></strong> / <strong><?php echo count($expectedThemesColumns); ?></strong> columns
                </div>
                <?php if (!empty($themesMissing)): ?>
                    <div class="summary-item missing">
                        Missing: <?php echo implode(', ', $themesMissing); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="section">
            <h2>Pages Table</h2>
            <table>
                <thead>
                    <tr>
                        <th>Column Name</th>
                        <th>Status</th>
                        <th>Data Type</th>
                        <th>Nullable</th>
                        <th>Default</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expectedPagesColumns as $col): ?>
                        <?php 
                        $found = in_array($col, $pagesFound);
                        $colData = null;
                        if ($found) {
                            foreach ($pagesColumns as $pc) {
                                if ($pc['COLUMN_NAME'] === $col) {
                                    $colData = $pc;
                                    break;
                                }
                            }
                        }
                        ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($col); ?></code></td>
                            <td>
                                <?php if ($found): ?>
                                    <span class="check">✅ Found</span>
                                <?php else: ?>
                                    <span class="missing">❌ Missing</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $found ? htmlspecialchars($colData['DATA_TYPE']) : '-'; ?></td>
                            <td><?php echo $found ? htmlspecialchars($colData['IS_NULLABLE']) : '-'; ?></td>
                            <td><?php echo $found ? htmlspecialchars($colData['COLUMN_DEFAULT'] ?? 'NULL') : '-'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="summary">
                <strong>Summary:</strong>
                <div class="summary-item">
                    Found: <strong><?php echo count($pagesFound); ?></strong> / <strong><?php echo count($expectedPagesColumns); ?></strong> columns
                </div>
                <?php if (!empty($pagesMissing)): ?>
                    <div class="summary-item missing">
                        Missing: <?php echo implode(', ', $pagesMissing); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="section">
            <h2>Index Check</h2>
            <div class="summary">
                <div class="summary-item">
                    <code>idx_user_id</code> on <code>themes.user_id</code>:
                    <?php if ($indexExists): ?>
                        <span class="check">✅ Exists</span>
                    <?php else: ?>
                        <span class="missing">❌ Missing</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php if (!$migrationComplete): ?>
            <div class="section">
                <h2>Next Steps</h2>
                <p>To complete the migration, run the SQL from <code>DATABASE_MIGRATION_INSTRUCTIONS.md</code> in phpMyAdmin.</p>
                <p>Or use the migration script: <code>database/migrate_theme_system.php</code></p>
            </div>
        <?php else: ?>
            <div class="section">
                <h2>✅ All Systems Ready!</h2>
                <p>Your database migration is complete. You can now use all theme system features:</p>
                <ul>
                    <li>✅ User-created themes</li>
                    <li>✅ Page background customization</li>
                    <li>✅ Widget styling</li>
                    <li>✅ Spatial effects</li>
                    <li>✅ Color extraction</li>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="section" style="margin-top: 3rem; padding-top: 2rem; border-top: 1px solid #ddd; font-size: 0.875rem; color: #666;">
            <p><strong>Check Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
            <p><strong>Database:</strong> <?php echo htmlspecialchars($pdo->query('SELECT DATABASE()')->fetchColumn()); ?></p>
        </div>
    </div>
</body>
</html>

