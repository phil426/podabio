<?php
/**
 * API endpoint for documentation files
 * Handles listing and serving markdown files from the docs/ directory
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? 'list';
$docsDir = __DIR__ . '/../docs';

if (!is_dir($docsDir)) {
    http_response_code(500);
    echo json_encode(['error' => 'Documentation directory not found']);
    exit;
}

/**
 * Recursively scan directory and build hierarchical structure
 */
function scanDocsDirectory($dir, $basePath = ''): array {
    $structure = [];
    $items = scandir($dir);
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        
        $fullPath = $dir . '/' . $item;
        $relativePath = $basePath ? $basePath . '/' . $item : $item;
        
        // Prevent directory traversal
        if (strpos($relativePath, '..') !== false) {
            continue;
        }
        
        if (is_dir($fullPath)) {
            $children = scanDocsDirectory($fullPath, $relativePath);
            $structure[] = [
                'name' => $item,
                'type' => 'folder',
                'path' => $relativePath,
                'children' => $children
            ];
        } elseif (is_file($fullPath) && pathinfo($item, PATHINFO_EXTENSION) === 'md') {
            $structure[] = [
                'name' => $item,
                'type' => 'file',
                'path' => $relativePath
            ];
        }
    }
    
    // Sort: folders first, then files, both alphabetically
    usort($structure, function($a, $b) {
        if ($a['type'] !== $b['type']) {
            return $a['type'] === 'folder' ? -1 : 1;
        }
        return strcasecmp($a['name'], $b['name']);
    });
    
    return $structure;
}

/**
 * Get markdown file content
 */
function getMarkdownContent($filePath, $docsDir): ?string {
    // Prevent directory traversal
    if (strpos($filePath, '..') !== false) {
        return null;
    }
    
    $fullPath = $docsDir . '/' . $filePath;
    
    // Ensure file is within docs directory
    $realDocsDir = realpath($docsDir);
    $realFilePath = realpath($fullPath);
    
    if ($realFilePath === false || strpos($realFilePath, $realDocsDir) !== 0) {
        return null;
    }
    
    if (!file_exists($fullPath) || !is_file($fullPath)) {
        return null;
    }
    
    // Only allow .md files
    if (pathinfo($fullPath, PATHINFO_EXTENSION) !== 'md') {
        return null;
    }
    
    return file_get_contents($fullPath);
}

try {
    if ($action === 'list') {
        $structure = scanDocsDirectory($docsDir);
        echo json_encode([
            'success' => true,
            'structure' => $structure
        ]);
    } elseif ($action === 'file' || $action === 'get') {
        $filePath = $_GET['file'] ?? '';
        
        if (empty($filePath)) {
            http_response_code(400);
            echo json_encode(['error' => 'File path is required']);
            exit;
        }
        
        $content = getMarkdownContent($filePath, $docsDir);
        
        if ($content === null) {
            http_response_code(404);
            echo json_encode(['error' => 'File not found']);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'content' => $content,
            'path' => $filePath
        ]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
