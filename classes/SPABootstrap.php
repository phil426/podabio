<?php
/**
 * SPA Bootstrap
 * Generates window global variables and bootstrap data for React app
 * PodaBio
 */

require_once __DIR__ . '/../config/spa-config.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/feature-flags.php';
require_once __DIR__ . '/../includes/helpers.php';

class SPABootstrap {
    private $csrfToken;
    private $appUrl;
    private $features;
    
    public function __construct() {
        $this->csrfToken = generateCSRFToken();
        $this->appUrl = defined('APP_URL') ? APP_URL : '';
        $this->features = $this->loadFeatures();
    }
    
    /**
     * Load feature flags for window globals
     * 
     * @return array Feature flags array
     */
    private function loadFeatures(): array {
        return [
            'account_workspace' => feature_flag('admin_account_workspace', true),
        ];
    }
    
    /**
     * Generate window global variables JavaScript
     * 
     * @return string JavaScript code to inject window globals
     */
    public function generateWindowGlobals(): string {
        $csrfVar = getSPAConfig('window_globals.csrf_token', '__CSRF_TOKEN__');
        $appUrlVar = getSPAConfig('window_globals.app_url', '__APP_URL__');
        $featuresVar = getSPAConfig('window_globals.features', '__FEATURES__');
        
        $output = "<script>\n";
        $output .= "        window." . h($csrfVar) . " = '" . h($this->csrfToken) . "';\n";
        $output .= "        window." . h($appUrlVar) . " = '" . h($this->appUrl) . "';\n";
        $output .= "        window." . h($featuresVar) . " = " . json_encode(
            $this->features,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
        ) . ";\n";
        $output .= "    </script>";
        
        return $output;
    }
    
    /**
     * Get CSRF token
     * 
     * @return string CSRF token
     */
    public function getCsrfToken(): string {
        return $this->csrfToken;
    }
    
    /**
     * Get application URL
     * 
     * @return string Application URL
     */
    public function getAppUrl(): string {
        return $this->appUrl;
    }
    
    /**
     * Get feature flags
     * 
     * @return array Feature flags
     */
    public function getFeatures(): array {
        return $this->features;
    }
}

