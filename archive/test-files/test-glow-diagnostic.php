<?php
/**
 * Glow Diagnostic Test
 * Tests if glow settings are being saved and loaded correctly
 */

require_once __DIR__ . '/classes/Theme.php';
require_once __DIR__ . '/classes/ThemeCSSGenerator.php';

echo "=== GLOW DIAGNOSTIC TEST ===\n\n";

// Connect to database (adjust credentials as needed)
$host = 'localhost';
$dbname = 'podinbio';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get a theme with glow settings
    $stmt = $pdo->query("SELECT id, name, widget_styles FROM themes WHERE widget_styles LIKE '%glow%' LIMIT 1");
    $theme = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($theme) {
        echo "✅ Found theme with glow: " . $theme['name'] . " (ID: " . $theme['id'] . ")\n";
        $widgetStyles = json_decode($theme['widget_styles'], true);
        echo "   widget_styles: " . json_encode($widgetStyles, JSON_PRETTY_PRINT) . "\n\n";
        
        // Test CSS generation
        $mockPage = ['id' => 1, 'theme_id' => $theme['id']];
        $generator = new ThemeCSSGenerator($mockPage, $theme);
        $css = $generator->generateCompleteStyleBlock();
        
        // Check for glow CSS
        if (strpos($css, 'glow-pulse') !== false) {
            echo "✅ glow-pulse animation found in CSS\n";
        } else {
            echo "❌ glow-pulse animation NOT found in CSS\n";
        }
        
        if (preg_match('/\.widget-item\s*\{[^}]*box-shadow:[^}]*rgba/s', $css)) {
            echo "✅ glow box-shadow found in CSS\n";
            // Extract the box-shadow line
            preg_match('/\.widget-item\s*\{[^}]*box-shadow:([^;]+);/s', $css, $matches);
            if (!empty($matches)) {
                echo "   box-shadow value: " . trim($matches[1]) . "\n";
            }
        } else {
            echo "❌ glow box-shadow NOT found in CSS\n";
        }
        
    } else {
        echo "❌ No theme found with glow settings\n";
        echo "   Please set border effect to 'Glow' and save a theme first\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    echo "   (This is expected if database credentials are different)\n";
}

echo "\n=== TEST COMPLETE ===\n";

