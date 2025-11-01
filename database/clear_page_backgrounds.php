<?php
/**
 * Clear custom backgrounds from all pages to use theme defaults
 */

require_once __DIR__ . '/../config/database.php';

$pdo = getDB();

echo "Clearing Custom Page Backgrounds\n";
echo "==================================\n\n";

$stmt = $pdo->prepare("UPDATE pages SET page_background = NULL, widget_background = NULL, widget_border_color = NULL");
$stmt->execute();

$affected = $stmt->rowCount();
echo "✓ Cleared custom backgrounds from {$affected} pages\n";

