<?php
/**
 * SPA (Single Page Application) Configuration
 * PodaBio - Configuration for React admin dashboard
 * 
 * Centralizes all SPA-related settings including file paths, dev server settings,
 * window global variable names, and environment detection.
 */

// SPA Directory Paths
$GLOBALS['SPA_CONFIG'] = [
    'paths' => [
        // Distribution directory (production build)
        'dist_dir' => getenv('SPA_DIST_DIR') ?: __DIR__ . '/../admin-ui/dist',
        
        // Vite manifest file location
        'manifest_file' => getenv('SPA_MANIFEST_FILE') ?: __DIR__ . '/../admin-ui/dist/.vite/manifest.json',
        
        // Key used in Vite manifest to find entry point
        'manifest_key' => getenv('SPA_MANIFEST_KEY') ?: 'src/main.tsx',
        
        // URL base path for production assets
        'dist_url_base' => getenv('SPA_DIST_URL_BASE') ?: '/admin-ui/dist',
    ],
    
    'dev_server' => [
        // Development server host
        'host' => getenv('VITE_DEV_HOST') ?: 'localhost',
        
        // Development server port
        'port' => (int)(getenv('VITE_DEV_PORT') ?: 5174),
        
        // Socket connection timeout (seconds)
        'timeout' => (float)(getenv('VITE_DEV_TIMEOUT') ?: 0.1),
        
        // Entry point file in dev mode
        'entry_point' => getenv('VITE_ENTRY_POINT') ?: 'src/main.tsx',
        
        // React Refresh runtime path
        'react_refresh_path' => getenv('VITE_REACT_REFRESH_PATH') ?: '@react-refresh',
        
        // Vite client script path
        'vite_client_path' => getenv('VITE_CLIENT_PATH') ?: '@vite/client',
    ],
    
    'window_globals' => [
        // CSRF token variable name
        'csrf_token' => getenv('SPA_WINDOW_CSRF_TOKEN') ?: '__CSRF_TOKEN__',
        
        // Application URL variable name
        'app_url' => getenv('SPA_WINDOW_APP_URL') ?: '__APP_URL__',
        
        // Admin panel variable name (may be deprecated)
        'admin_panel' => getenv('SPA_WINDOW_ADMIN_PANEL') ?: '__ADMIN_PANEL__',
        
        // Feature flags variable name
        'features' => getenv('SPA_WINDOW_FEATURES') ?: '__FEATURES__',
    ],
    
    'environment' => [
        // Environment mode: 'development', 'staging', 'production'
        'mode' => getenv('APP_ENV') ?: 'production',
        
        // Boolean flag for development mode
        'dev_mode' => (getenv('APP_ENV') ?: 'production') === 'development',
        
        // Whether manifest file is required (false in dev, true in production)
        'manifest_required' => (getenv('APP_ENV') ?: 'production') !== 'development',
    ],
];

/**
 * Get SPA configuration value
 * 
 * Supports dot notation for nested keys:
 * - getSPAConfig('paths.dist_dir')
 * - getSPAConfig('dev_server.port')
 * 
 * @param string|null $key Dot-notation key path, or null to get entire config
 * @param mixed $default Default value if key not found
 * @return mixed Configuration value or default
 */
function getSPAConfig(?string $key = null, $default = null) {
    $config = $GLOBALS['SPA_CONFIG'] ?? [];
    
    // Return entire config if no key specified
    if ($key === null) {
        return $config;
    }
    
    // Navigate through nested keys using dot notation
    $keys = explode('.', $key);
    $value = $config;
    
    foreach ($keys as $k) {
        if (!is_array($value) || !isset($value[$k])) {
            return $default;
        }
        $value = $value[$k];
    }
    
    return $value;
}

/**
 * Get SPA distribution directory path
 * 
 * @return string Absolute path to SPA dist directory
 */
function getSPADistDir(): string {
    return getSPAConfig('paths.dist_dir');
}

/**
 * Get SPA manifest file path
 * 
 * @return string Absolute path to Vite manifest file
 */
function getSPAManifestPath(): string {
    return getSPAConfig('paths.manifest_file');
}

/**
 * Check if Vite dev server is running
 * 
 * @return bool True if dev server is accessible, false otherwise
 */
function isViteDevServerRunning(): bool {
    $host = getSPAConfig('dev_server.host', 'localhost');
    $port = getSPAConfig('dev_server.port', 5174);
    $timeout = getSPAConfig('dev_server.timeout', 0.1);
    
    $connection = @fsockopen($host, $port, $errno, $errstr, $timeout);
    
    if ($connection) {
        fclose($connection);
        return true;
    }
    
    return false;
}

/**
 * Get dev server URL for entry point
 * 
 * @return string Full URL to dev server entry point
 */
function getDevServerEntryUrl(): string {
    $host = getSPAConfig('dev_server.host', 'localhost');
    $port = getSPAConfig('dev_server.port', 5174);
    $entryPoint = getSPAConfig('dev_server.entry_point', 'src/main.tsx');
    
    return "http://{$host}:{$port}/{$entryPoint}";
}

/**
 * Get dev server URL for React Refresh
 * 
 * @return string Full URL to React Refresh runtime
 */
function getDevServerRefreshUrl(): string {
    $host = getSPAConfig('dev_server.host', 'localhost');
    $port = getSPAConfig('dev_server.port', 5174);
    $refreshPath = getSPAConfig('dev_server.react_refresh_path', '@react-refresh');
    
    return "http://{$host}:{$port}/{$refreshPath}";
}

/**
 * Get dev server URL for Vite client
 * 
 * @return string Full URL to Vite client script
 */
function getDevServerViteClientUrl(): string {
    $host = getSPAConfig('dev_server.host', 'localhost');
    $port = getSPAConfig('dev_server.port', 5174);
    $clientPath = getSPAConfig('dev_server.vite_client_path', '@vite/client');
    
    return "http://{$host}:{$port}/{$clientPath}";
}

/**
 * Check if running in development mode
 * 
 * @return bool True if in development mode
 */
function isSPADevMode(): bool {
    return getSPAConfig('environment.dev_mode', false);
}

