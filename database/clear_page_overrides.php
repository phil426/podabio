<?php
require_once __DIR__ . '/../config/database.php';

echo "Clearing Page Overrides\n";
echo "========================\n\n";

try {
    $pdo = getDB();
    $stmt = $pdo->prepare("UPDATE pages SET spatial_effect = NULL, widget_styles = NULL");
    $stmt->execute();
    $count = $stmt->rowCount();
    echo "✓ Cleared overrides from {$count} pages\n";
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>

