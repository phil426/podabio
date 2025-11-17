<?php
/**
 * Add Four New Themes to Database
 * Run this script once to add new themes
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

echo "🎨 Adding four new themes...\n\n";

$themes = [
    [
        'name' => 'Ocean',
        'colors' => [
            'primary' => '#0a2540',
            'secondary' => '#f0f7ff',
            'accent' => '#00a8e8'
        ],
        'fonts' => [
            'heading' => 'Montserrat',
            'body' => 'Open Sans'
        ]
    ],
    [
        'name' => 'Sunset',
        'colors' => [
            'primary' => '#2d1b3d',
            'secondary' => '#fff5e6',
            'accent' => '#ff6b35'
        ],
        'fonts' => [
            'heading' => 'Playfair Display',
            'body' => 'Lato'
        ]
    ],
    [
        'name' => 'Forest',
        'colors' => [
            'primary' => '#1a3a2e',
            'secondary' => '#f5faf7',
            'accent' => '#4ade80'
        ],
        'fonts' => [
            'heading' => 'Merriweather',
            'body' => 'Source Sans Pro'
        ]
    ],
    [
        'name' => 'Violet',
        'colors' => [
            'primary' => '#4c1d95',
            'secondary' => '#faf5ff',
            'accent' => '#a855f7'
        ],
        'fonts' => [
            'heading' => 'Poppins',
            'body' => 'Nunito'
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

