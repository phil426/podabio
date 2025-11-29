<?php
/**
 * Security Functions
 * PodaBio
 */

/**
 * Sanitize input string
 * @param string $input
 * @return string
 */
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate and sanitize URL
 * @param string $url
 * @return string|false
 */
function sanitizeUrl($url) {
    $url = trim($url);
    
    // Add http:// if no scheme
    if (!preg_match('#^https?://#i', $url)) {
        $url = 'https://' . $url;
    }
    
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        return $url;
    }
    
    return false;
}

/**
 * Rate limiting check
 * @param string $key
 * @param int $limit
 * @param int $window
 * @return bool
 */
function checkRateLimit($key, $limit = 10, $window = 60) {
    $cacheFile = sys_get_temp_dir() . '/ratelimit_' . md5($key) . '.json';
    $now = time();
    
    if (file_exists($cacheFile)) {
        $data = json_decode(file_get_contents($cacheFile), true);
        
        // Remove old entries
        $data['requests'] = array_filter($data['requests'], function($timestamp) use ($now, $window) {
            return ($now - $timestamp) < $window;
        });
        
        if (count($data['requests']) >= $limit) {
            return false;
        }
    } else {
        $data = ['requests' => []];
    }
    
    $data['requests'][] = $now;
    file_put_contents($cacheFile, json_encode($data));
    
    return true;
}

/**
 * Get client IP address
 * @return string
 */
function getClientIP() {
    $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    
    foreach ($ipKeys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = $_SERVER[$key];
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Hash password
 * @param string $password
 * @return string
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password
 * @param string $password
 * @param string $hash
 * @return bool
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Get effective upload limit (minimum of app config, PHP ini, and server limits)
 * @return int Maximum upload size in bytes
 */
function getEffectiveUploadLimit() {
    static $cachedLimit = null;
    
    if ($cachedLimit !== null) {
        return $cachedLimit;
    }
    
    // Start with application limit
    $limits = [MAX_FILE_SIZE];
    
    // Check PHP upload_max_filesize
    $uploadMaxFilesize = ini_get('upload_max_filesize');
    if ($uploadMaxFilesize) {
        $limits[] = parsePhpSize($uploadMaxFilesize);
    }
    
    // Check PHP post_max_size (must be at least as large as upload_max_filesize)
    $postMaxSize = ini_get('post_max_size');
    if ($postMaxSize) {
        $limits[] = parsePhpSize($postMaxSize);
    }
    
    // Return the minimum limit (most restrictive)
    $cachedLimit = min($limits);
    return $cachedLimit;
}

/**
 * Parse PHP size string to bytes (e.g., "10M", "5M", "2G")
 * @param string $size
 * @return int Size in bytes
 */
function parsePhpSize($size) {
    $size = trim($size);
    $last = strtolower($size[strlen($size) - 1]);
    $value = (int) $size;
    
    switch ($last) {
        case 'g':
            $value *= 1024;
        case 'm':
            $value *= 1024;
        case 'k':
            $value *= 1024;
    }
    
    return $value;
}

/**
 * Validate file upload
 * @param array $file
 * @param array $allowedTypes
 * @param int $maxSize
 * @return array ['valid' => bool, 'error' => string|null]
 */
function validateFileUpload($file, $allowedTypes = null, $maxSize = null) {
    $allowedTypes = $allowedTypes ?? ALLOWED_IMAGE_TYPES;
    
    // Use effective upload limit if no maxSize specified
    if ($maxSize === null) {
        $maxSize = getEffectiveUploadLimit();
    } else {
        // Ensure we don't exceed effective limit even if a higher limit is specified
        $maxSize = min($maxSize, getEffectiveUploadLimit());
    }
    
    // Check if file was uploaded
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['valid' => false, 'error' => 'No file uploaded'];
    }
    
    // Check file size
    if ($file['size'] > $maxSize) {
        return ['valid' => false, 'error' => 'File size exceeds maximum allowed size'];
    }
    
    // Check file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return ['valid' => false, 'error' => 'Invalid file type'];
    }
    
    // Check file extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Build allowed extensions list - JPEG files can have both .jpg and .jpeg extensions
    $allowedExtensions = [];
    foreach ($allowedTypes as $mime) {
        switch ($mime) {
            case 'image/jpeg':
                $allowedExtensions[] = 'jpg';
                $allowedExtensions[] = 'jpeg'; // Allow both .jpg and .jpeg
                break;
            case 'image/png':
                $allowedExtensions[] = 'png';
                break;
            case 'image/gif':
                $allowedExtensions[] = 'gif';
                break;
            case 'image/webp':
                $allowedExtensions[] = 'webp';
                break;
        }
    }
    
    if (!in_array($extension, $allowedExtensions)) {
        return ['valid' => false, 'error' => 'Invalid file extension. Allowed: ' . implode(', ', array_unique($allowedExtensions))];
    }
    
    return ['valid' => true, 'error' => null, 'mime_type' => $mimeType, 'extension' => $extension];
}

/**
 * Generate secure filename
 * @param string $originalName
 * @return string
 */
function generateSecureFilename($originalName) {
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    return bin2hex(random_bytes(16)) . '.' . $extension;
}


