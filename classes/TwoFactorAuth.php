<?php
/**
 * Two-Factor Authentication Helper Class
 * PodaBio - Handles TOTP and Email-based 2FA
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';

use OTPHP\TOTP;

class TwoFactorAuth {
    
    /**
     * Generate TOTP secret and QR code URI
     * @param string $email User's email address
     * @param string $secret Optional existing secret (for regeneration)
     * @return array ['secret' => string, 'qr_code_url' => string]
     */
    public static function generateTOTPSecret($email, $secret = null) {
        if ($secret) {
            $totp = TOTP::create($secret);
        } else {
            $totp = TOTP::create();
        }
        
        $totp->setLabel($email);
        $totp->setIssuer(APP_NAME);
        
        $secret = $totp->getSecret();
        $qrCodeUrl = $totp->getProvisioningUri();
        
        return [
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl
        ];
    }
    
    /**
     * Verify TOTP code
     * @param string $secret TOTP secret
     * @param string $code 6-digit code from user
     * @return bool True if code is valid
     */
    public static function verifyTOTPCode($secret, $code) {
        if (empty($secret) || empty($code) || strlen($code) !== 6) {
            return false;
        }
        
        try {
            $totp = TOTP::create($secret);
            // Allow 1 window before/after for clock drift (30 seconds each)
            return $totp->verify($code, null, 1);
        } catch (Exception $e) {
            error_log("TOTP verification error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate random 6-digit email code
     * @return string 6-digit code
     */
    public static function generateEmailCode() {
        return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Send email code to user
     * @param string $email User's email
     * @param string $code 6-digit code
     * @return bool True if email sent successfully
     */
    public static function sendEmailCode($email, $code) {
        $subject = 'Your ' . APP_NAME . ' Login Code';
        $message = self::getEmailCodeTemplate($code);
        return sendEmail($email, $subject, $message);
    }
    
    /**
     * Get email template for 2FA code
     * @param string $code 6-digit code
     * @return string HTML email content
     */
    private static function getEmailCodeTemplate($code) {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
                .content { background: #f9fafb; padding: 30px; }
                .code-box { background: #ffffff; border: 2px solid #667eea; border-radius: 8px; padding: 20px; text-align: center; margin: 20px 0; }
                .code { font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #667eea; font-family: monospace; }
                .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 12px; }
                .warning { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>' . h(APP_NAME) . '</h1>
                </div>
                <div class="content">
                    <h2>Your Login Code</h2>
                    <p>Use this code to complete your login:</p>
                    <div class="code-box">
                        <div class="code">' . h($code) . '</div>
                    </div>
                    <p>This code will expire in 10 minutes.</p>
                    <div class="warning">
                        <strong>⚠️ Security Notice:</strong> If you did not request this code, please ignore this email and consider changing your password.
                    </div>
                    <p>If you did not attempt to log in, please secure your account immediately.</p>
                </div>
                <div class="footer">
                    <p>&copy; ' . date('Y') . ' ' . h(APP_NAME) . '. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>';
    }
    
    /**
     * Generate backup codes for account recovery
     * @param int $count Number of codes to generate (default 10)
     * @return array Array of backup codes
     */
    public static function generateBackupCodes($count = 10) {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            // Generate 8-character alphanumeric codes
            $codes[] = strtoupper(bin2hex(random_bytes(4)));
        }
        return $codes;
    }
    
    /**
     * Verify backup code and mark as used
     * @param int $userId User ID
     * @param string $code Backup code to verify
     * @return bool True if code is valid and was successfully marked as used
     */
    public static function verifyBackupCode($userId, $code) {
        $user = fetchOne(
            "SELECT two_factor_backup_codes FROM users WHERE id = ?",
            [$userId]
        );
        
        if (!$user || empty($user['two_factor_backup_codes'])) {
            return false;
        }
        
        $backupCodes = json_decode($user['two_factor_backup_codes'], true);
        if (!is_array($backupCodes)) {
            return false;
        }
        
        // Find and remove the code
        $codeIndex = array_search(strtoupper($code), array_map('strtoupper', $backupCodes));
        if ($codeIndex === false) {
            return false;
        }
        
        // Remove the used code
        unset($backupCodes[$codeIndex]);
        $backupCodes = array_values($backupCodes); // Re-index array
        
        // Update database
        $pdo = getDB();
        $stmt = $pdo->prepare("UPDATE users SET two_factor_backup_codes = ? WHERE id = ?");
        $stmt->execute([json_encode($backupCodes), $userId]);
        
        return true;
    }
    
    /**
     * Check if user has 2FA enabled
     * @param int $userId User ID
     * @return array ['enabled' => bool, 'method' => string|null]
     */
    public static function get2FAStatus($userId) {
        $user = fetchOne(
            "SELECT two_factor_enabled, two_factor_method FROM users WHERE id = ?",
            [$userId]
        );
        
        if (!$user) {
            return ['enabled' => false, 'method' => null];
        }
        
        return [
            'enabled' => (bool)($user['two_factor_enabled'] ?? false),
            'method' => $user['two_factor_method'] ?? null
        ];
    }
    
    /**
     * Rate limit check for 2FA attempts
     * @param string $key Rate limit key (e.g., '2fa_attempts_' . $userId)
     * @param int $maxAttempts Maximum attempts allowed
     * @param int $windowSeconds Time window in seconds
     * @return bool True if within rate limit
     */
    public static function checkRateLimit($key, $maxAttempts = 5, $windowSeconds = 300) {
        return checkRateLimit($key, $maxAttempts, $windowSeconds);
    }
}

