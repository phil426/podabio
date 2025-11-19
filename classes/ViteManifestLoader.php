<?php
/**
 * Vite Manifest Loader
 * Handles loading of Vite build manifest and dev server detection
 * PodaBio
 */

require_once __DIR__ . '/../config/spa-config.php';

class ViteManifestLoader {
    private $manifestPath;
    private $manifestKey;
    private $distUrlBase;
    
    public function __construct() {
        $this->manifestPath = getSPAManifestPath();
        $this->manifestKey = getSPAConfig('paths.manifest_key', 'index.html');
        $this->distUrlBase = getSPAConfig('paths.dist_url_base', '/admin-ui/dist');
    }
    
    /**
     * Load manifest file and extract entry point information
     * 
     * @return array|null Array with 'script' and 'css' keys, or null if manifest not found/invalid
     */
    public function loadManifest(): ?array {
        if (!file_exists($this->manifestPath)) {
            return null;
        }
        
        $manifestContent = file_get_contents($this->manifestPath);
        if ($manifestContent === false) {
            return null;
        }
        
        $manifest = json_decode($manifestContent, true);
        if (!is_array($manifest) || !isset($manifest[$this->manifestKey])) {
            return null;
        }
        
        $entry = $manifest[$this->manifestKey];
        $result = [
            'script' => $this->distUrlBase . '/' . $entry['file']
        ];
        
        if (!empty($entry['css']) && is_array($entry['css'])) {
            $result['css'] = $this->distUrlBase . '/' . $entry['css'][0];
        }
        
        return $result;
    }
    
    /**
     * Check if dev server is running
     * 
     * @return bool True if dev server is accessible
     */
    public function isDevServerRunning(): bool {
        return isViteDevServerRunning();
    }
    
    /**
     * Get entry script source (production or dev)
     * 
     * @return string|null Script source URL, or null if unavailable
     */
    public function getScriptSrc(): ?string {
        // Try production manifest first
        $manifest = $this->loadManifest();
        if ($manifest && isset($manifest['script'])) {
            // If dev server is running, prefer dev mode
            if ($this->isDevServerRunning() || isSPADevMode()) {
                return getDevServerEntryUrl();
            }
            return $manifest['script'];
        }
        
        // Fall back to dev server if running
        if ($this->isDevServerRunning() || isSPADevMode()) {
            return getDevServerEntryUrl();
        }
        
        return null;
    }
    
    /**
     * Get CSS stylesheet href (production only)
     * 
     * @return string|null CSS href URL, or null if not available
     */
    public function getCssHref(): ?string {
        $manifest = $this->loadManifest();
        return $manifest['css'] ?? null;
    }
    
    /**
     * Determine if running in development mode
     * 
     * @return bool True if in dev mode
     */
    public function isDevMode(): bool {
        // Dev mode if manifest doesn't exist or dev server is running
        if (!file_exists($this->manifestPath)) {
            return true;
        }
        
        if (isSPADevMode()) {
            return true;
        }
        
        if ($this->isDevServerRunning()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get all script tags for dev mode (React Refresh + Vite Client)
     * 
     * @return array Array of script tag HTML strings
     */
    public function getDevModeScripts(): array {
        if (!$this->isDevMode()) {
            return [];
        }
        
        $refreshUrl = getDevServerRefreshUrl();
        $viteClientUrl = getDevServerViteClientUrl();
        
        return [
            // React Refresh Runtime
            '<script type="module">
            import RefreshRuntime from "' . htmlspecialchars($refreshUrl, ENT_QUOTES, 'UTF-8') . '";
            RefreshRuntime.injectIntoGlobalHook(window);
            window.$RefreshReg$ = () => {};
            window.$RefreshSig$ = () => (type) => type;
            window.__vite_plugin_react_preamble_installed__ = true;
        </script>',
            // Vite Client
            '<script type="module" src="' . htmlspecialchars($viteClientUrl, ENT_QUOTES, 'UTF-8') . '"></script>'
        ];
    }
}

