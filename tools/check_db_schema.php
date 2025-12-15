<?php
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDB();
    $stmt = $pdo->query("DESCRIBE pages");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN); // Get first column (Field name)

    echo "Columns in 'pages' table:\n";
    foreach ($columns as $col) {
        if (strpos($col, 'page_background_image') !== false) {
            echo " [FOUND] $col\n";
        } else {
            // echo " $col\n"; // Comment out to reduce noise, unless needed
        }
    }

    // Explicit check
    $required = [
        'page_background_image_url',
        'page_background_image_overlay',
        'page_background_image_scale',
        'page_background_image_blur',
        'page_background_image_focal_x',
        'page_background_image_focal_y'
    ];

    echo "\nVerification:\n";
    foreach ($required as $req) {
        if (in_array($req, $columns)) {
            echo "✅ $req exists\n";
        } else {
            echo "❌ $req MISSING\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
