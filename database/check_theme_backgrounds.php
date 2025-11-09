<?php
require_once __DIR__ . '/../config/database.php';

echo "Theme Backgrounds Check\n";
echo "=======================\n\n";

try {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT id, name, page_background, spatial_effect, widget_styles FROM themes ORDER BY id");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Theme ID: {$row['id']}\n";
        echo "  Name: {$row['name']}\n";
        echo "  Page Background: " . ($row['page_background'] ? substr($row['page_background'], 0, 80) . '...' : 'NULL') . "\n";
        echo "  Spatial Effect: {$row['spatial_effect']}\n";
        
        if (!empty($row['widget_styles'])) {
            $styles = json_decode($row['widget_styles'], true);
            if ($styles) {
                echo "  Widget BG: " . ($styles['widget_background'] ?? 'Not in widget_styles JSON') . "\n";
            }
        }
        echo "\n";
    }
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>

