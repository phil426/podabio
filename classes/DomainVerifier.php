<?php
/**
 * Domain Verifier Class
 * Podn.Bio - Verifies DNS configuration for custom domains
 */

class DomainVerifier {
    
    /**
     * Verify if domain points to our server
     * @param string $domain
     * @return array ['verified' => bool, 'message' => string, 'records' => array]
     */
    public function verifyDomain($domain) {
        // Normalize domain (remove www, http, https, trailing slash)
        $domain = $this->normalizeDomain($domain);
        
        if (!$this->isValidDomain($domain)) {
            return [
                'verified' => false,
                'message' => 'Invalid domain format',
                'records' => []
            ];
        }
        
        // Check DNS records
        $records = @dns_get_record($domain, DNS_A + DNS_CNAME + DNS_TXT);
        
        if ($records === false || empty($records)) {
            return [
                'verified' => false,
                'message' => 'No DNS records found. Please ensure your DNS is configured correctly.',
                'records' => []
            ];
        }
        
        // Get our server IP (you'll need to set this)
        $serverIP = $this->getServerIP();
        
        // Check if A record or CNAME points to our server
        $pointingToUs = false;
        $foundRecords = [];
        
        foreach ($records as $record) {
            $foundRecords[] = [
                'type' => $record['type'],
                'value' => $record['host'] ?? ($record['target'] ?? ($record['ip'] ?? ''))
            ];
            
            if ($record['type'] === 'A' && isset($record['ip'])) {
                if ($record['ip'] === $serverIP) {
                    $pointingToUs = true;
                }
            }
            
            if ($record['type'] === 'CNAME' && isset($record['target'])) {
                // Check if CNAME resolves to our domain
                $targetRecords = @dns_get_record($record['target'], DNS_A);
                foreach ($targetRecords as $targetRecord) {
                    if (isset($targetRecord['ip']) && $targetRecord['ip'] === $serverIP) {
                        $pointingToUs = true;
                    }
                }
            }
        }
        
        return [
            'verified' => $pointingToUs,
            'message' => $pointingToUs 
                ? 'Domain is correctly configured' 
                : 'Domain DNS does not point to our server. Please check your DNS settings.',
            'records' => $foundRecords
        ];
    }
    
    /**
     * Normalize domain string
     * @param string $domain
     * @return string
     */
    private function normalizeDomain($domain) {
        // Remove protocol
        $domain = preg_replace('#^https?://#i', '', $domain);
        
        // Remove www prefix
        $domain = preg_replace('#^www\.#i', '', $domain);
        
        // Remove trailing slash
        $domain = rtrim($domain, '/');
        
        // Remove path
        $domain = parse_url('http://' . $domain, PHP_URL_HOST);
        
        return strtolower(trim($domain));
    }
    
    /**
     * Validate domain format
     * @param string $domain
     * @return bool
     */
    public function isValidDomain($domain) {
        // Basic domain validation
        if (empty($domain) || strlen($domain) > 255) {
            return false;
        }
        
        // Check for valid domain format
        if (!preg_match('/^([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i', $domain)) {
            return false;
        }
        
        // Don't allow our own domain
        $ownDomains = ['getphily.com', 'poda.bio'];
        foreach ($ownDomains as $ownDomain) {
            if ($domain === $ownDomain || substr($domain, -strlen('.' . $ownDomain)) === '.' . $ownDomain) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get server IP address
     * @return string
     */
    private function getServerIP() {
        // This should be set in constants or config
        // For now, try to get it from environment or return a placeholder
        if (defined('SERVER_IP')) {
            return SERVER_IP;
        }
        
        // Try to get from $_SERVER
        $ip = $_SERVER['SERVER_ADDR'] ?? '';
        if (!empty($ip) && filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
        
        // Default fallback (should be configured)
        return '156.67.73.201'; // From HOSTING_INFO.md
    }
    
    /**
     * Check if domain DNS is properly configured
     * @param string $domain
     * @return bool
     */
    public function checkDNSConfiguration($domain) {
        $domain = $this->normalizeDomain($domain);
        
        if (!$this->isValidDomain($domain)) {
            return false;
        }
        
        $result = $this->verifyDomain($domain);
        return $result['verified'];
    }
}

