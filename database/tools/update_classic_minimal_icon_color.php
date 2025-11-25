<?php
/**
 * Update Classic Minimal Theme Social Icon Color
 * Changes the default social icon color to #1E293B
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';

$pdo = getDB();

$themeName = 'Classic Minimal';
$newIconColor = '#1E293B';

try {
    $pdo->beginTransaction();
    
    echo "🎨 Updating Classic Minimal Theme Social Icon Color...\n\n";
    
    // Step 1: Find the theme
    echo "Step 1: Finding theme...\n";
    $stmt = $pdo->prepare("SELECT id, name, iconography_tokens FROM themes WHERE name = ?");
    $stmt->execute([$themeName]);
    $theme = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$theme) {
        echo "   ❌ Theme '$themeName' not found in database.\n";
        $pdo->rollBack();
        exit(1);
    }
    
    $themeId = $theme['id'];
    echo "   ✅ Found theme: {$theme['name']} (ID: $themeId)\n\n";
    
    // Step 2: Parse existing iconography_tokens
    echo "Step 2: Parsing iconography_tokens...\n";
    $iconographyTokens = [];
    if (!empty($theme['iconography_tokens'])) {
        if (is_string($theme['iconography_tokens'])) {
            $iconographyTokens = json_decode($theme['iconography_tokens'], true) ?: [];
        } else {
            $iconographyTokens = $theme['iconography_tokens'];
        }
    }
    
    // Initialize if empty
    if (empty($iconographyTokens)) {
        $iconographyTokens = [
            'color' => '#2563EB',
            'size' => '48px',
            'spacing' => '0.75rem'
        ];
    }
    
    $oldColor = $iconographyTokens['color'] ?? '#2563EB';
    echo "   ℹ️  Current icon color: $oldColor\n";
    
    // Step 3: Update the color
    echo "Step 3: Updating icon color...\n";
    $iconographyTokens['color'] = $newIconColor;
    echo "   ✅ New icon color: $newIconColor\n\n";
    
    // Step 4: Update the database
    echo "Step 4: Updating database...\n";
    $iconographyTokensJson = json_encode($iconographyTokens, JSON_UNESCAPED_SLASHES);
    $stmt = $pdo->prepare("UPDATE themes SET iconography_tokens = ? WHERE id = ?");
    $stmt->execute([$iconographyTokensJson, $themeId]);
    $updated = $stmt->rowCount();
    
    if ($updated > 0) {
        echo "   ✅ Updated theme successfully\n\n";
    } else {
        echo "   ⚠️  No rows updated\n\n";
        $pdo->rollBack();
        exit(1);
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo "==========================================\n";
    echo "✅ Classic Minimal theme updated!\n";
    echo "   Theme: $themeName (ID: $themeId)\n";
    echo "   Social icon color: $oldColor → $newIconColor\n";
    echo "==========================================\n";
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

