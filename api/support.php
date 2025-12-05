<?php
/**
 * Support Articles API
 * PodaBio - API for managing support/help documentation
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/SupportArticle.php';
require_once __DIR__ . '/../classes/SupportCategory.php';

header('Content-Type: application/json');

// Check if user is admin for write operations
function requireAdmin() {
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        exit;
    }
    
    // Check if user is root admin (phil624@gmail.com)
    $userId = getCurrentUserId();
    $user = fetchOne("SELECT email FROM users WHERE id = ?", [$userId]);
    
    if (!$user || $user['email'] !== 'phil624@gmail.com') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Admin access required']);
        exit;
    }
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    // ==================== PUBLIC ENDPOINTS ====================
    
    case 'get_categories':
        // Get all categories with article counts (public)
        $categories = SupportCategory::getWithArticles();
        echo json_encode([
            'success' => true,
            'categories' => $categories
        ]);
        break;
        
    case 'get_articles':
        // Get published articles (public)
        $categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;
        $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 100) : 50;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        
        $articles = SupportArticle::getPublished($categoryId, $limit, $offset);
        $total = SupportArticle::count(true);
        
        echo json_encode([
            'success' => true,
            'articles' => $articles,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset
        ]);
        break;
        
    case 'get_article':
        // Get single article by slug (public)
        $slug = sanitizeInput($_GET['slug'] ?? '');
        
        if (empty($slug)) {
            echo json_encode(['success' => false, 'error' => 'Slug is required']);
            exit;
        }
        
        $article = SupportArticle::getBySlug($slug, true);
        
        if (!$article) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Article not found']);
            exit;
        }
        
        // Increment view count
        SupportArticle::incrementViewCount($article['id']);
        
        echo json_encode([
            'success' => true,
            'article' => $article
        ]);
        break;
        
    case 'search':
        // Search articles (public)
        $query = sanitizeInput($_GET['q'] ?? '');
        
        if (empty($query) || strlen($query) < 2) {
            echo json_encode(['success' => false, 'error' => 'Search query must be at least 2 characters']);
            exit;
        }
        
        $articles = SupportArticle::search($query, true);
        
        echo json_encode([
            'success' => true,
            'articles' => $articles,
            'query' => $query
        ]);
        break;
        
    case 'get_popular':
        // Get popular articles (public)
        $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 20) : 10;
        $articles = SupportArticle::getPopular($limit);
        
        echo json_encode([
            'success' => true,
            'articles' => $articles
        ]);
        break;
        
    case 'get_recent':
        // Get recent articles (public)
        $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 20) : 10;
        $articles = SupportArticle::getRecent($limit);
        
        echo json_encode([
            'success' => true,
            'articles' => $articles
        ]);
        break;
        
    // ==================== ADMIN ENDPOINTS ====================
    
    case 'admin_get_all':
        // Get all articles including unpublished (admin)
        requireAdmin();
        
        $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 200) : 100;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        
        $articles = SupportArticle::getAll($limit, $offset);
        $categories = SupportCategory::getAll();
        $total = SupportArticle::count(false);
        
        echo json_encode([
            'success' => true,
            'articles' => $articles,
            'categories' => $categories,
            'total' => $total
        ]);
        break;
        
    case 'admin_get_article':
        // Get article by ID including unpublished (admin)
        requireAdmin();
        
        $id = (int)($_GET['id'] ?? 0);
        
        if (empty($id)) {
            echo json_encode(['success' => false, 'error' => 'Article ID is required']);
            exit;
        }
        
        $article = SupportArticle::getById($id);
        
        if (!$article) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Article not found']);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'article' => $article
        ]);
        break;
        
    case 'create_article':
        // Create new article (admin)
        requireAdmin();
        
        $data = [
            'title' => sanitizeInput($_POST['title'] ?? ''),
            'content' => $_POST['content'] ?? '', // Don't sanitize HTML content
            'slug' => sanitizeInput($_POST['slug'] ?? ''),
            'category_id' => $_POST['category_id'] ?? null,
            'tags' => sanitizeInput($_POST['tags'] ?? ''),
            'published' => isset($_POST['published']) ? (int)$_POST['published'] : 0
        ];
        
        if (empty($data['title']) || empty($data['content'])) {
            echo json_encode(['success' => false, 'error' => 'Title and content are required']);
            exit;
        }
        
        $articleId = SupportArticle::create($data);
        
        if ($articleId) {
            $article = SupportArticle::getById($articleId);
            echo json_encode([
                'success' => true,
                'article' => $article,
                'message' => 'Article created successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to create article']);
        }
        break;
        
    case 'update_article':
        // Update article (admin)
        requireAdmin();
        
        $id = (int)($_POST['id'] ?? 0);
        
        if (empty($id)) {
            echo json_encode(['success' => false, 'error' => 'Article ID is required']);
            exit;
        }
        
        $data = [];
        
        if (isset($_POST['title'])) {
            $data['title'] = sanitizeInput($_POST['title']);
        }
        if (isset($_POST['content'])) {
            $data['content'] = $_POST['content']; // Don't sanitize HTML content
        }
        if (isset($_POST['slug'])) {
            $data['slug'] = sanitizeInput($_POST['slug']);
        }
        if (array_key_exists('category_id', $_POST)) {
            $data['category_id'] = $_POST['category_id'];
        }
        if (isset($_POST['tags'])) {
            $data['tags'] = sanitizeInput($_POST['tags']);
        }
        if (isset($_POST['published'])) {
            $data['published'] = (int)$_POST['published'];
        }
        
        $result = SupportArticle::update($id, $data);
        
        if ($result) {
            $article = SupportArticle::getById($id);
            echo json_encode([
                'success' => true,
                'article' => $article,
                'message' => 'Article updated successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update article']);
        }
        break;
        
    case 'delete_article':
        // Delete article (admin)
        requireAdmin();
        
        $id = (int)($_POST['id'] ?? 0);
        
        if (empty($id)) {
            echo json_encode(['success' => false, 'error' => 'Article ID is required']);
            exit;
        }
        
        $result = SupportArticle::delete($id);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Article deleted successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to delete article']);
        }
        break;
        
    case 'create_category':
        // Create category (admin)
        requireAdmin();
        
        $data = [
            'name' => sanitizeInput($_POST['name'] ?? ''),
            'slug' => sanitizeInput($_POST['slug'] ?? ''),
            'description' => sanitizeInput($_POST['description'] ?? ''),
            'display_order' => isset($_POST['display_order']) ? (int)$_POST['display_order'] : 0
        ];
        
        if (empty($data['name'])) {
            echo json_encode(['success' => false, 'error' => 'Category name is required']);
            exit;
        }
        
        $categoryId = SupportCategory::create($data);
        
        if ($categoryId) {
            $category = SupportCategory::getById($categoryId);
            echo json_encode([
                'success' => true,
                'category' => $category,
                'message' => 'Category created successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to create category']);
        }
        break;
        
    case 'update_category':
        // Update category (admin)
        requireAdmin();
        
        $id = (int)($_POST['id'] ?? 0);
        
        if (empty($id)) {
            echo json_encode(['success' => false, 'error' => 'Category ID is required']);
            exit;
        }
        
        $data = [];
        
        if (isset($_POST['name'])) {
            $data['name'] = sanitizeInput($_POST['name']);
        }
        if (isset($_POST['slug'])) {
            $data['slug'] = sanitizeInput($_POST['slug']);
        }
        if (isset($_POST['description'])) {
            $data['description'] = sanitizeInput($_POST['description']);
        }
        if (isset($_POST['display_order'])) {
            $data['display_order'] = (int)$_POST['display_order'];
        }
        
        $result = SupportCategory::update($id, $data);
        
        if ($result) {
            $category = SupportCategory::getById($id);
            echo json_encode([
                'success' => true,
                'category' => $category,
                'message' => 'Category updated successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update category']);
        }
        break;
        
    case 'delete_category':
        // Delete category (admin)
        requireAdmin();
        
        $id = (int)($_POST['id'] ?? 0);
        
        if (empty($id)) {
            echo json_encode(['success' => false, 'error' => 'Category ID is required']);
            exit;
        }
        
        $result = SupportCategory::delete($id);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Category deleted successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to delete category']);
        }
        break;
        
    case 'admin_get_categories':
        // Get all categories for admin
        requireAdmin();
        
        $categories = SupportCategory::getAll();
        
        echo json_encode([
            'success' => true,
            'categories' => $categories
        ]);
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        break;
}


