<?php
/**
 * Add Four New Gradient Themes to Database
 * Cool gradient-inspired color schemes
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

echo "🎨 Adding four cool gradient themes...\n\n";

$themes = [
    [
        'name' => 'Aurora',
        'colors' => [
            'primary' => '#1e1b4b',      // Deep indigo (night sky start)
            'secondary' => '#f8fafc',  // Cool white (aurora light)
            'accent' => '#6366f1'       // Bright indigo (aurora glow)
        ],
        'fonts' => [
            'heading' => 'Space Grotesk',
            'body' => 'Inter'
        ]
    ],
    [
        'name' => 'Coral',
        'colors' => [
            'primary' => '#7c2d12',     // Deep terracotta (coral base)
            'secondary' => '#fff7ed',   // Warm cream (beach sand)
            'accent' => '#fb7185'       // Vibrant coral pink
        ],
        'fonts' => [
            'heading' => 'Bebas Neue',
            'body' => 'DM Sans'
        ]
    ],
    [
        'name' => 'Emerald',
        'colors' => [
            'primary' => '#064e3b',     // Deep forest green
            'secondary' => '#ecfdf5',   // Mint cream
            'accent' => '#10b981'       // Bright emerald
        ],
        'fonts' => [
            'heading' => 'Barlow',
            'body' => 'Work Sans'
        ]
    ],
    [
        'name' => 'Cosmic',
        'colors' => [
            'primary' => '#0f172a',     // Deep space black
            'secondary' => '#f1f5f9',   // Starlight silver
            'accent' => '#8b5cf6'       // Cosmic purple
        ],
        'fonts' => [
            'heading' => 'Orbitron',
            'body' => 'Rajdhani'
        ]
    ]
];

$pdo = getDB();
$added = 0;
$skipped = 0;

foreach ($themes as $theme) {
    // Check if theme already exists
    $existing = fetchOne("SELECT id FROM themes WHERE name = ?", [$theme['name']]);
    
    if ($existing) {
        echo "⏭️  Theme '{$theme['name']}' already exists, skipping...\n";
        $skipped++;
        continue;
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO themes (name, colors, fonts, preview_image, is_active)
            VALUES (?, ?, ?, NULL, 1)
        ");
        
        $stmt->execute([
            $theme['name'],
            json_encode($theme['colors']),
            json_encode($theme['fonts'])
        ]);
        
        echo "✅ Added theme: {$theme['name']}\n";
        echo "   Colors: Primary {$theme['colors']['primary']}, Secondary {$theme['colors']['secondary']}, Accent {$theme['colors']['accent']}\n";
        echo "   Fonts: Heading {$theme['fonts']['heading']}, Body {$theme['fonts']['body']}\n\n";
        $added++;
        
    } catch (PDOException $e) {
        echo "❌ Error adding theme '{$theme['name']}': " . $e->getMessage() . "\n\n";
    }
}

echo "\n==========================================\n";
echo "✅ Complete!\n";
echo "   Added: $added themes\n";
if ($skipped > 0) {
    echo "   Skipped: $skipped themes (already exist)\n";
}
echo "==========================================\n";

