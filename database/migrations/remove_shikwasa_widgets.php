<?php
/**
 * Remove Shikwasa Widget Types from Database
 * Deletes all widgets with widget_type 'podcast_player' or 'podcast_player_full'
 */

require_once __DIR__ . '/../config/database.php';

$pdo = getDB();

echo "🗑️  Removing Shikwasa podcast player widgets from database...\n\n";

try {
    // Count widgets to be deleted
    $count = fetchOne("SELECT COUNT(*) as count FROM widgets WHERE widget_type IN ('podcast_player', 'podcast_player_full')");
    $totalCount = $count['count'] ?? 0;
    
    if ($totalCount == 0) {
        echo "✅ No Shikwasa widgets found in database.\n";
        exit(0);
    }
    
    echo "Found {$totalCount} Shikwasa widget(s) to delete:\n";
    
    // List widgets to be deleted
    $widgets = fetchAll("SELECT id, page_id, widget_type, title FROM widgets WHERE widget_type IN ('podcast_player', 'podcast_player_full')");
    foreach ($widgets as $widget) {
        echo "  - Widget ID {$widget['id']}: {$widget['title']} (Type: {$widget['widget_type']}, Page ID: {$widget['page_id']})\n";
    }
    
    echo "\n⚠️  WARNING: This will permanently delete these widgets!\n";
    echo "Executing deletion...\n\n";
    
    // Delete the widgets
    $stmt = $pdo->prepare("DELETE FROM widgets WHERE widget_type IN ('podcast_player', 'podcast_player_full')");
    $stmt->execute();
    
    $deleted = $stmt->rowCount();
    
    echo "✅ Successfully deleted {$deleted} widget(s).\n";
    echo "==========================================\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

