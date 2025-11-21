<?php
require_once __DIR__ . '/../config/database.php';

$pdo = getDB();
$stmt = $pdo->prepare("SELECT id, name, spatial_effect FROM themes WHERE id = 34");
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Theme ID: {$row['id']}\n";
echo "Name: {$row['name']}\n";
echo "Spatial Effect: " . ($row['spatial_effect'] ?? 'NULL') . "\n";
?>

