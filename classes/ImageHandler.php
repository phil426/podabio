<?php
/**
 * Image Handler Class
 * Podn.Bio
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/security.php';

class ImageHandler {
    
    /**
     * Upload and process image
     * @param array $file
     * @param string $type ('profile', 'background', 'thumbnail')
     * @return array ['success' => bool, 'path' => string|null, 'error' => string|null]
     */
    public function uploadImage($file, $type = 'thumbnail') {
        // Validate file
        $validation = validateFileUpload($file);
        
        if (!$validation['valid']) {
            return ['success' => false, 'path' => null, 'error' => $validation['error']];
        }
        
        // Determine upload directory
        $uploadDir = $this->getUploadDir($type);
        
        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                return ['success' => false, 'path' => null, 'error' => 'Failed to create upload directory'];
            }
        }
        
        // Generate secure filename
        $extension = $validation['extension'];
        $filename = generateSecureFilename($file['name']);
        $filepath = $uploadDir . '/' . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            return ['success' => false, 'path' => null, 'error' => 'Failed to save file'];
        }
        
        // Resize if needed
        $this->resizeImage($filepath, $type);
        
        // Return relative path
        $relativePath = str_replace(ROOT_PATH, '', $filepath);
        
        return [
            'success' => true,
            'path' => $relativePath,
            'url' => APP_URL . $relativePath,
            'error' => null
        ];
    }
    
    /**
     * Get upload directory for type
     * @param string $type
     * @return string
     */
    private function getUploadDir($type) {
        switch ($type) {
            case 'profile':
                return UPLOAD_PROFILES;
            case 'background':
                return UPLOAD_BACKGROUNDS;
            case 'thumbnail':
                return UPLOAD_THUMBNAILS;
            case 'blog':
                return UPLOAD_BLOG;
            case 'theme_image':
                // Temporary uploads for color extraction
                $tempDir = UPLOAD_PATH . '/theme_temp';
                if (!is_dir($tempDir)) {
                    mkdir($tempDir, 0755, true);
                }
                return $tempDir;
            default:
                return UPLOAD_PATH;
        }
    }
    
    /**
     * Resize image based on type
     * @param string $filepath
     * @param string $type
     * @return bool
     */
    private function resizeImage($filepath, $type) {
        if (!extension_loaded('gd')) {
            return false;
        }
        
        $imageInfo = getimagesize($filepath);
        if ($imageInfo === false) {
            return false;
        }
        
        list($width, $height, $imageType) = $imageInfo;
        
        // Determine target dimensions
        switch ($type) {
            case 'profile':
                $targetWidth = PROFILE_IMAGE_WIDTH;
                $targetHeight = PROFILE_IMAGE_HEIGHT;
                break;
            case 'thumbnail':
                $targetWidth = THUMBNAIL_WIDTH;
                $targetHeight = THUMBNAIL_HEIGHT;
                break;
            case 'background':
                // Keep aspect ratio, just resize if too large
                if ($width <= BACKGROUND_IMAGE_MAX_WIDTH && $height <= BACKGROUND_IMAGE_MAX_HEIGHT) {
                    return true; // No resize needed
                }
                $ratio = min(BACKGROUND_IMAGE_MAX_WIDTH / $width, BACKGROUND_IMAGE_MAX_HEIGHT / $height);
                $targetWidth = (int)($width * $ratio);
                $targetHeight = (int)($height * $ratio);
                break;
            default:
                return true; // No resize
        }
        
        // Create image resource
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($filepath);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($filepath);
                break;
            case IMAGETYPE_GIF:
                $source = imagecreatefromgif($filepath);
                break;
            case IMAGETYPE_WEBP:
                $source = imagecreatefromwebp($filepath);
                break;
            default:
                return false;
        }
        
        if (!$source) {
            return false;
        }
        
        // Create resized image
        $destination = imagecreatetruecolor($targetWidth, $targetHeight);
        
        // Preserve transparency for PNG/GIF
        if ($imageType == IMAGETYPE_PNG || $imageType == IMAGETYPE_GIF) {
            imagealphablending($destination, false);
            imagesavealpha($destination, true);
            $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
            imagefilledrectangle($destination, 0, 0, $targetWidth, $targetHeight, $transparent);
        }
        
        // Resize
        imagecopyresampled(
            $destination, $source,
            0, 0, 0, 0,
            $targetWidth, $targetHeight,
            $width, $height
        );
        
        // Save
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                imagejpeg($destination, $filepath, 85);
                break;
            case IMAGETYPE_PNG:
                imagepng($destination, $filepath, 6);
                break;
            case IMAGETYPE_GIF:
                imagegif($destination, $filepath);
                break;
            case IMAGETYPE_WEBP:
                imagewebp($destination, $filepath, 85);
                break;
        }
        
        imagedestroy($source);
        imagedestroy($destination);
        
        return true;
    }
    
    /**
     * Delete image
     * @param string $path
     * @return bool
     */
    public function deleteImage($path) {
        $fullPath = ROOT_PATH . $path;
        if (file_exists($fullPath) && is_file($fullPath)) {
            return unlink($fullPath);
        }
        return false;
    }
}


