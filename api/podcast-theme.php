<?php
/**
 * Podcast Theme API Endpoint
 * Handles color extraction, theme generation, and color shuffling
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/ColorExtractor.php';
require_once __DIR__ . '/../classes/PodcastThemeGenerator.php';

header('Content-Type: application/json');

requireAuth();

$user = getCurrentUser();
$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$action = $_POST['action'] ?? '';

$colorExtractor = new ColorExtractor();
$themeGenerator = new PodcastThemeGenerator();

switch ($action) {
    case 'extract_colors':
        $imageUrl = $_POST['image_url'] ?? '';
        
        if (empty($imageUrl)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Image URL is required']);
            exit;
        }
        
        try {
            $colors = $colorExtractor->extractColors($imageUrl, 5);
            echo json_encode([
                'success' => true,
                'colors' => $colors
            ]);
        } catch (Exception $e) {
            error_log("PodcastTheme API extract_colors error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to extract colors: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'generate_theme':
        $colorsInput = $_POST['colors'] ?? [];
        // Handle JSON stringified array from FormData
        if (is_string($colorsInput)) {
            $colors = json_decode($colorsInput, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $colors = [];
            }
        } else {
            $colors = is_array($colorsInput) ? $colorsInput : [];
        }
        
        $podcastName = $_POST['podcast_name'] ?? null;
        $podcastDescription = $_POST['podcast_description'] ?? null;
        
        if (empty($colors) || !is_array($colors) || count($colors) < 2) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'At least 2 colors are required']);
            exit;
        }
        
        try {
            $themeData = $themeGenerator->generateTheme(
                array_slice($colors, 0, 5), // Use up to 5 colors
                $podcastName,
                $podcastDescription
            );
            
            echo json_encode([
                'success' => true,
                'theme_data' => $themeData
            ]);
        } catch (Exception $e) {
            error_log("PodcastTheme API generate_theme error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to generate theme: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'shuffle_colors':
        $colorsInput = $_POST['colors'] ?? [];
        // Handle JSON stringified array from FormData
        if (is_string($colorsInput)) {
            $colors = json_decode($colorsInput, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $colors = [];
            }
        } else {
            $colors = is_array($colorsInput) ? $colorsInput : [];
        }
        
        if (empty($colors) || !is_array($colors) || count($colors) < 2) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'At least 2 colors are required']);
            exit;
        }
        
        try {
            $shuffled = $themeGenerator->shuffleColors(array_slice($colors, 0, 5));
            
            echo json_encode([
                'success' => true,
                'colors' => $shuffled
            ]);
        } catch (Exception $e) {
            error_log("PodcastTheme API shuffle_colors error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to shuffle colors: ' . $e->getMessage()
            ]);
        }
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        break;
}

