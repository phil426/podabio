<?php
/**
 * Blog Posts API Endpoint
 * Handles CRUD operations for blog posts
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/security.php';

header('Content-Type: application/json');

requireAuth();

$user = getCurrentUser();
$userId = $user['id'];
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($method === 'GET') {
    if ($action === 'list') {
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 20);
        $offset = ($page - 1) * $limit;
        $categoryId = !empty($_GET['category_id']) ? (int)$_GET['category_id'] : null;
        
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if ($categoryId) {
            $whereClause .= " AND p.category_id = ?";
            $params[] = $categoryId;
        }
        
        $posts = fetchAll(
            "SELECT p.*, c.name as category_name, c.slug as category_slug 
             FROM blog_posts p 
             LEFT JOIN blog_categories c ON p.category_id = c.id 
             $whereClause 
             ORDER BY p.created_at DESC 
             LIMIT ? OFFSET ?",
            array_merge($params, [$limit, $offset])
        );
        
        $total = (int)fetchOne("SELECT COUNT(*) as count FROM blog_posts p $whereClause", $params)['count'];
        
        echo json_encode([
            'success' => true,
            'posts' => $posts,
            'total' => $total,
            'page' => $page,
            'limit' => $limit
        ]);
        exit;
    }
    
    if ($action === 'get') {
        $postId = (int)($_GET['id'] ?? 0);
        if (!$postId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Post ID required']);
            exit;
        }
        
        $post = fetchOne(
            "SELECT p.*, c.name as category_name, c.slug as category_slug 
             FROM blog_posts p 
             LEFT JOIN blog_categories c ON p.category_id = c.id 
             WHERE p.id = ?",
            [$postId]
        );
        
        if (!$post) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Post not found']);
            exit;
        }
        
        echo json_encode(['success' => true, 'post' => $post]);
        exit;
    }
    
    // Default: return all posts
    $posts = fetchAll(
        "SELECT p.*, c.name as category_name, c.slug as category_slug 
         FROM blog_posts p 
         LEFT JOIN blog_categories c ON p.category_id = c.id 
         ORDER BY p.created_at DESC"
    );
    
    echo json_encode(['success' => true, 'posts' => $posts]);
    exit;
}

if ($method === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit;
    }
    
    switch ($action) {
        case 'create':
            $title = sanitizeInput($_POST['title'] ?? '');
            $slug = sanitizeInput($_POST['slug'] ?? '');
            $content = $_POST['content'] ?? '';
            $excerpt = sanitizeInput($_POST['excerpt'] ?? '');
            $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
            $published = isset($_POST['published']) ? 1 : 0;
            $featuredImage = sanitizeInput($_POST['featured_image'] ?? '');
            
            if (empty($title) || empty($slug)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Title and slug are required']);
                exit;
            }
            
            // Auto-generate slug if not provided
            if (empty($slug)) {
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
            }
            
            // Check if slug already exists
            $existing = fetchOne("SELECT id FROM blog_posts WHERE slug = ?", [$slug]);
            if ($existing) {
                $slug .= '-' . time();
            }
            
            $postId = executeQuery(
                "INSERT INTO blog_posts (title, slug, content, excerpt, category_id, author_id, published, featured_image, created_at, updated_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
                [$title, $slug, $content, $excerpt, $categoryId, $userId, $published, $featuredImage]
            );
            
            $post = fetchOne(
                "SELECT p.*, c.name as category_name, c.slug as category_slug 
                 FROM blog_posts p 
                 LEFT JOIN blog_categories c ON p.category_id = c.id 
                 WHERE p.id = ?",
                [$postId]
            );
            
            echo json_encode(['success' => true, 'post' => $post, 'post_id' => $postId]);
            break;
            
        case 'update':
            $postId = (int)($_POST['post_id'] ?? 0);
            if (!$postId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Post ID required']);
                exit;
            }
            
            $title = sanitizeInput($_POST['title'] ?? '');
            $slug = sanitizeInput($_POST['slug'] ?? '');
            $content = $_POST['content'] ?? '';
            $excerpt = sanitizeInput($_POST['excerpt'] ?? '');
            $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
            $published = isset($_POST['published']) ? 1 : 0;
            $featuredImage = sanitizeInput($_POST['featured_image'] ?? '');
            
            if (empty($title) || empty($slug)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Title and slug are required']);
                exit;
            }
            
            // Check if slug already exists for another post
            $existing = fetchOne("SELECT id FROM blog_posts WHERE slug = ? AND id != ?", [$slug, $postId]);
            if ($existing) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Slug already exists']);
                exit;
            }
            
            executeQuery(
                "UPDATE blog_posts 
                 SET title = ?, slug = ?, content = ?, excerpt = ?, category_id = ?, published = ?, featured_image = ?, updated_at = NOW() 
                 WHERE id = ?",
                [$title, $slug, $content, $excerpt, $categoryId, $published, $featuredImage, $postId]
            );
            
            $post = fetchOne(
                "SELECT p.*, c.name as category_name, c.slug as category_slug 
                 FROM blog_posts p 
                 LEFT JOIN blog_categories c ON p.category_id = c.id 
                 WHERE p.id = ?",
                [$postId]
            );
            
            echo json_encode(['success' => true, 'post' => $post]);
            break;
            
        case 'delete':
            $postId = (int)($_POST['post_id'] ?? 0);
            if (!$postId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Post ID required']);
                exit;
            }
            
            executeQuery("DELETE FROM blog_posts WHERE id = ?", [$postId]);
            echo json_encode(['success' => true]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);


