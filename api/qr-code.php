<?php
/**
 * QR Code Generator API
 * Generates and caches QR codes for pages
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/Page.php';

// Set content type to image/png
header('Content-Type: image/png');
header('Cache-Control: public, max-age=31536000'); // Cache for 1 year

// Get page ID or username from query string
$pageId = $_GET['page_id'] ?? null;
$username = $_GET['username'] ?? null;

if (!$pageId && !$username) {
    http_response_code(400);
    die('Missing page_id or username parameter');
}

$pageClass = new Page();
$page = null;

if ($pageId) {
    // Get page by ID - need to query directly
    require_once __DIR__ . '/../config/database.php';
    $page = fetchOne("SELECT p.*, u.email FROM pages p JOIN users u ON p.user_id = u.id WHERE p.id = ? AND p.is_active = 1", [(int)$pageId]);
} elseif ($username) {
    $page = $pageClass->getByUsername($username);
}

if (!$page || !$page['is_active']) {
    http_response_code(404);
    die('Page not found');
}

// Generate page URL - match the logic from page.php
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$currentDomain = $_SERVER['HTTP_HOST'];
$mainDomains = ['getphily.com', 'www.getphily.com', 'poda.bio', 'www.poda.bio', 'localhost', '127.0.0.1'];

// Check if page has a custom domain
if (!empty($page['custom_domain'])) {
    // Use custom domain
    $pageUrl = $protocol . '://' . $page['custom_domain'];
} elseif (!in_array(strtolower($currentDomain), $mainDomains)) {
    // Current domain is a custom domain - use it
    $pageUrl = $protocol . '://' . $currentDomain;
} else {
    // Use username with main domain
    $mainDomain = in_array(strtolower($currentDomain), ['localhost', '127.0.0.1']) 
        ? $currentDomain 
        : 'poda.bio';
    $pageUrl = $protocol . '://' . $mainDomain . '/' . $page['username'];
}

// Check cache directory
$cacheDir = __DIR__ . '/../uploads/qr-codes';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

// Generate cache filename
$cacheFile = $cacheDir . '/' . md5($pageUrl) . '.png';

// If cached and still valid, serve it
if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 86400) {
    // Cache is less than 24 hours old
    readfile($cacheFile);
    exit;
}

// Generate QR code using simple PHP approach
// Using a simple QR code generation approach with GD library
function generateQRCode($text, $size = 256) {
    // Use Google Charts API as a simple solution (or we can use a PHP library)
    // For now, using a simple approach with an external service
    $url = 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . urlencode($text);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $imageData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $imageData) {
        return $imageData;
    }
    
    return false;
}

// Generate QR code
$qrImageData = generateQRCode($pageUrl, 512);

if ($qrImageData) {
    // Save to cache
    file_put_contents($cacheFile, $qrImageData);
    // Output the image
    echo $qrImageData;
} else {
    http_response_code(500);
    die('Failed to generate QR code');
}

