<?php
/**
 * Media Library Class
 * PodaBio
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/ImageHandler.php';

class MediaLibrary {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDB();
    }
    
    /**
     * Upload image to media library
     * @param array $file
     * @param int $userId
     * @return array ['success' => bool, 'media_id' => int|null, 'path' => string|null, 'url' => string|null, 'error' => string|null]
     */
    public function uploadToLibrary($file, $userId) {
        $imageHandler = new ImageHandler();
        $result = $imageHandler->uploadToMediaLibrary($file, $userId);
        
        if (!$result['success']) {
            return $result;
        }
        
        // Create database entry
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO user_media (user_id, filename, file_path, file_url, file_size, mime_type, uploaded_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $userId,
                $result['filename'],
                $result['path'],
                $result['url'],
                $result['file_size'],
                $result['mime_type']
            ]);
            
            $mediaId = $this->pdo->lastInsertId();
            
            return [
                'success' => true,
                'media_id' => $mediaId,
                'path' => $result['path'],
                'url' => $result['url'],
                'error' => null
            ];
        } catch (PDOException $e) {
            // Delete uploaded file if database insert fails
            if (isset($result['path'])) {
                $imageHandler->deleteImage($result['path']);
            }
            error_log("Media library upload failed: " . $e->getMessage());
            error_log("Media library upload failed - SQL State: " . $e->getCode());
            error_log("Media library upload failed - Data: " . print_r([
                'user_id' => $userId,
                'filename' => $result['filename'] ?? 'N/A',
                'path' => $result['path'] ?? 'N/A',
                'url' => $result['url'] ?? 'N/A',
                'file_size' => $result['file_size'] ?? 'N/A',
                'mime_type' => $result['mime_type'] ?? 'N/A'
            ], true));
            return [
                'success' => false,
                'media_id' => null,
                'path' => null,
                'url' => null,
                'error' => 'Failed to save media library entry: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get user's media items (paginated)
     * @param int $userId
     * @param array $options ['page' => int, 'per_page' => int, 'search' => string]
     * @return array ['success' => bool, 'media' => array, 'total' => int, 'page' => int, 'per_page' => int]
     */
    public function getUserMedia($userId, $options = []) {
        $page = isset($options['page']) ? max(1, (int)$options['page']) : 1;
        $perPage = isset($options['per_page']) ? max(1, (int)$options['per_page']) : MEDIA_PER_PAGE;
        $search = isset($options['search']) ? trim($options['search']) : '';
        
        $offset = ($page - 1) * $perPage;
        
        $whereClause = "user_id = ?";
        $params = [$userId];
        
        if (!empty($search)) {
            $whereClause .= " AND filename LIKE ?";
            $params[] = '%' . $search . '%';
        }
        
        // Get total count
        $countStmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM user_media WHERE $whereClause");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get paginated results
        $stmt = $this->pdo->prepare("
            SELECT * FROM user_media 
            WHERE $whereClause 
            ORDER BY uploaded_at DESC 
            LIMIT ? OFFSET ?
        ");
        $params[] = $perPage;
        $params[] = $offset;
        $stmt->execute($params);
        
        $media = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $media[] = [
                'id' => (int)$row['id'],
                'user_id' => (int)$row['user_id'],
                'filename' => $row['filename'],
                'file_path' => $row['file_path'],
                'file_url' => $row['file_url'],
                'file_size' => (int)$row['file_size'],
                'mime_type' => $row['mime_type'],
                'uploaded_at' => $row['uploaded_at'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at']
            ];
        }
        
        return [
            'success' => true,
            'media' => $media,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int)ceil($total / $perPage)
        ];
    }
    
    /**
     * Get single media item (with user verification)
     * @param int $mediaId
     * @param int $userId
     * @return array|null
     */
    public function getMediaItem($mediaId, $userId) {
        $media = fetchOne(
            "SELECT * FROM user_media WHERE id = ? AND user_id = ?",
            [$mediaId, $userId]
        );
        
        if (!$media) {
            return null;
        }
        
        return [
            'id' => (int)$media['id'],
            'user_id' => (int)$media['user_id'],
            'filename' => $media['filename'],
            'file_path' => $media['file_path'],
            'file_url' => $media['file_url'],
            'file_size' => (int)$media['file_size'],
            'mime_type' => $media['mime_type'],
            'uploaded_at' => $media['uploaded_at'],
            'created_at' => $media['created_at'],
            'updated_at' => $media['updated_at']
        ];
    }
    
    /**
     * Delete media item and file
     * @param int $mediaId
     * @param int $userId
     * @return array ['success' => bool, 'error' => string|null]
     */
    public function deleteMedia($mediaId, $userId) {
        // Get media item (verify ownership)
        $media = $this->getMediaItem($mediaId, $userId);
        
        if (!$media) {
            return ['success' => false, 'error' => 'Media item not found'];
        }
        
        // Delete file
        $imageHandler = new ImageHandler();
        $fileDeleted = $imageHandler->deleteImage($media['file_path']);
        
        // Delete database entry
        try {
            $stmt = $this->pdo->prepare("DELETE FROM user_media WHERE id = ? AND user_id = ?");
            $stmt->execute([$mediaId, $userId]);
            
            return ['success' => true, 'error' => null];
        } catch (PDOException $e) {
            error_log("Media library deletion failed: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to delete media library entry'];
        }
    }
    
    /**
     * Migrate existing image to media library
     * @param string $filePath Relative path from root
     * @param int $userId
     * @param array $metadata ['filename' => string, 'file_size' => int, 'mime_type' => string]
     * @return array ['success' => bool, 'media_id' => int|null, 'error' => string|null]
     */
    public function migrateExistingImage($filePath, $userId, $metadata = []) {
        $fullPath = ROOT_PATH . $filePath;
        
        // Check if file exists
        if (!file_exists($fullPath) || !is_file($fullPath)) {
            return ['success' => false, 'media_id' => null, 'error' => 'File not found'];
        }
        
        // Get file info if not provided
        $filename = $metadata['filename'] ?? basename($filePath);
        $fileSize = $metadata['file_size'] ?? filesize($fullPath);
        
        if (empty($metadata['mime_type'])) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $fullPath);
            finfo_close($finfo);
        } else {
            $mimeType = $metadata['mime_type'];
        }
        
        // Check if already in library
        $existing = fetchOne(
            "SELECT id FROM user_media WHERE user_id = ? AND file_path = ?",
            [$userId, $filePath]
        );
        
        if ($existing) {
            return [
                'success' => true,
                'media_id' => (int)$existing['id'],
                'error' => null,
                'already_exists' => true
            ];
        }
        
        // Create database entry
        try {
            $fileUrl = APP_URL . $filePath;
            
            $stmt = $this->pdo->prepare("
                INSERT INTO user_media (user_id, filename, file_path, file_url, file_size, mime_type, uploaded_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $userId,
                $filename,
                $filePath,
                $fileUrl,
                $fileSize,
                $mimeType
            ]);
            
            $mediaId = $this->pdo->lastInsertId();
            
            return [
                'success' => true,
                'media_id' => $mediaId,
                'error' => null,
                'already_exists' => false
            ];
        } catch (PDOException $e) {
            error_log("Media library migration failed: " . $e->getMessage());
            return [
                'success' => false,
                'media_id' => null,
                'error' => 'Failed to migrate image to media library'
            ];
        }
    }
}

