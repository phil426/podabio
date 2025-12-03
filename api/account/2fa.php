<?php
/**
 * Two-Factor Authentication API
 * Handles 2FA setup, verification, and management
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../classes/User.php';
require_once __DIR__ . '/../../classes/TwoFactorAuth.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

// Parse JSON body if present (for POST requests with JSON)
$raw = file_get_contents('php://input');
$jsonPayload = null;
if (!empty($raw)) {
    $jsonPayload = json_decode($raw, true);
}

// Get action from JSON body, POST, or GET (in that order)
$action = '';
if ($jsonPayload && isset($jsonPayload['action'])) {
    $action = $jsonPayload['action'];
} elseif (isset($_POST['action'])) {
    $action = $_POST['action'];
} elseif (isset($_GET['action'])) {
    $action = $_GET['action'];
}

$userId = getUserId();

// For login verification (no auth required yet - will be checked separately)
$isLoginVerification = ($action === 'verify_login_code' || $action === 'send_login_email_code');

if (!$isLoginVerification && !isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$pdo = getDB();

switch ($action) {
    case 'get_status':
        // Get current 2FA status
        $status = TwoFactorAuth::get2FAStatus($userId);
        $user = fetchOne("SELECT email FROM users WHERE id = ?", [$userId]);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'enabled' => $status['enabled'],
                'method' => $status['method'],
                'email' => $user['email'] ?? null
            ]
        ]);
        break;
        
    case 'generate_setup':
        // Generate TOTP secret and QR code for setup
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }
        
        try {
            $user = fetchOne("SELECT email FROM users WHERE id = ?", [$userId]);
            if (!$user) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'User not found']);
                exit;
            }
            
            $totpData = TwoFactorAuth::generateTOTPSecret($user['email']);
            
            // Store secret temporarily (not enabled yet - will be enabled after verification)
            $stmt = $pdo->prepare("UPDATE users SET two_factor_secret = ? WHERE id = ?");
            $stmt->execute([$totpData['secret'], $userId]);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'secret' => $totpData['secret'],
                    'qr_code_url' => $totpData['qr_code_url']
                ]
            ]);
        } catch (Exception $e) {
            error_log("2FA generate_setup error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to generate TOTP setup: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'verify_enable':
        // Verify TOTP code and enable 2FA
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }
        
        // Use already parsed JSON payload
        $payload = $jsonPayload ?? [];
        
        $code = $payload['code'] ?? '';
        $methodChoice = $payload['method'] ?? 'totp'; // 'totp', 'email', or 'both'
        
        if (empty($code) || strlen($code) !== 6) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid code format']);
            exit;
        }
        
        if (!in_array($methodChoice, ['totp', 'email', 'both'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid method']);
            exit;
        }
        
        $user = fetchOne("SELECT two_factor_secret, email FROM users WHERE id = ?", [$userId]);
        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'User not found']);
            exit;
        }
        
        // Verify code based on method
        $codeValid = false;
        
        if ($methodChoice === 'totp' || $methodChoice === 'both') {
            if (empty($user['two_factor_secret'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'TOTP secret not found. Please generate setup first.']);
                exit;
            }
            $codeValid = TwoFactorAuth::verifyTOTPCode($user['two_factor_secret'], $code);
        } elseif ($methodChoice === 'email') {
            // For email method, we'll send a code and verify it
            // This is a simplified flow - in production, you'd send code first, then verify
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Email method setup requires sending code first']);
            exit;
        }
        
        if (!$codeValid) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid verification code']);
            exit;
        }
        
        // Generate backup codes
        $backupCodes = TwoFactorAuth::generateBackupCodes(10);
        
        // Enable 2FA
        $stmt = $pdo->prepare("
            UPDATE users SET 
                two_factor_enabled = TRUE,
                two_factor_method = ?,
                two_factor_backup_codes = ?
            WHERE id = ?
        ");
        $stmt->execute([$methodChoice, json_encode($backupCodes), $userId]);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'backup_codes' => $backupCodes,
                'method' => $methodChoice
            ],
            'message' => 'Two-factor authentication enabled successfully'
        ]);
        break;
        
    case 'enable_email':
        // Enable email-only 2FA (simpler flow)
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }
        
        // Use already parsed JSON payload
        $payload = $jsonPayload ?? [];
        
        $code = $payload['code'] ?? '';
        
        $user = fetchOne("SELECT email, two_factor_email_code, two_factor_email_code_expires FROM users WHERE id = ?", [$userId]);
        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'User not found']);
            exit;
        }
        
        // Verify email code
        if (empty($user['two_factor_email_code']) || 
            empty($user['two_factor_email_code_expires']) ||
            strtotime($user['two_factor_email_code_expires']) < time()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Email code expired or not found. Please request a new code.']);
            exit;
        }
        
        if ($user['two_factor_email_code'] !== $code) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid verification code']);
            exit;
        }
        
        // Generate backup codes
        $backupCodes = TwoFactorAuth::generateBackupCodes(10);
        
        // Enable 2FA with email method
        $stmt = $pdo->prepare("
            UPDATE users SET 
                two_factor_enabled = TRUE,
                two_factor_method = 'email',
                two_factor_backup_codes = ?,
                two_factor_email_code = NULL,
                two_factor_email_code_expires = NULL
            WHERE id = ?
        ");
        $stmt->execute([json_encode($backupCodes), $userId]);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'backup_codes' => $backupCodes,
                'method' => 'email'
            ],
            'message' => 'Two-factor authentication enabled successfully'
        ]);
        break;
        
    case 'send_setup_email_code':
        // Send email code for email-only 2FA setup
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }
        
        $user = fetchOne("SELECT email FROM users WHERE id = ?", [$userId]);
        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'User not found']);
            exit;
        }
        
        // Rate limit: max 3 emails per 10 minutes
        $rateLimitKey = '2fa_setup_email_' . $userId;
        if (!TwoFactorAuth::checkRateLimit($rateLimitKey, 3, 600)) {
            http_response_code(429);
            echo json_encode(['success' => false, 'error' => 'Too many requests. Please wait before requesting another code.']);
            exit;
        }
        
        $code = TwoFactorAuth::generateEmailCode();
        $expiresAt = date('Y-m-d H:i:s', time() + 600); // 10 minutes
        
        // Store code temporarily
        $stmt = $pdo->prepare("
            UPDATE users SET 
                two_factor_email_code = ?,
                two_factor_email_code_expires = ?
            WHERE id = ?
        ");
        $stmt->execute([$code, $expiresAt, $userId]);
        
        // Send email
        $emailSent = TwoFactorAuth::sendEmailCode($user['email'], $code);
        
        if (!$emailSent) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to send email code']);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Verification code sent to your email'
        ]);
        break;
        
    case 'disable':
        // Disable 2FA (require password confirmation)
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }
        
        // Use already parsed JSON payload
        $payload = $jsonPayload ?? [];
        
        $password = $payload['password'] ?? '';
        
        // Verify password
        $user = fetchOne("SELECT password_hash FROM users WHERE id = ?", [$userId]);
        if (!$user || !verifyPassword($password, $user['password_hash'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Invalid password']);
            exit;
        }
        
        // Disable 2FA
        $stmt = $pdo->prepare("
            UPDATE users SET 
                two_factor_enabled = FALSE,
                two_factor_method = NULL,
                two_factor_secret = NULL,
                two_factor_backup_codes = NULL,
                two_factor_email_code = NULL,
                two_factor_email_code_expires = NULL
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Two-factor authentication disabled'
        ]);
        break;
        
    case 'regenerate_backup_codes':
        // Regenerate backup codes
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }
        
        $status = TwoFactorAuth::get2FAStatus($userId);
        if (!$status['enabled']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => '2FA is not enabled']);
            exit;
        }
        
        $backupCodes = TwoFactorAuth::generateBackupCodes(10);
        
        $stmt = $pdo->prepare("UPDATE users SET two_factor_backup_codes = ? WHERE id = ?");
        $stmt->execute([json_encode($backupCodes), $userId]);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'backup_codes' => $backupCodes
            ],
            'message' => 'Backup codes regenerated'
        ]);
        break;
        
    case 'send_login_email_code':
        // Send email code during login (no auth required)
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }
        
        // Use already parsed JSON payload
        $payload = $jsonPayload ?? [];
        
        $email = $payload['email'] ?? '';
        
        if (empty($email)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Email required']);
            exit;
        }
        
        // Find user by email
        $user = fetchOne("SELECT id, email, two_factor_enabled, two_factor_method FROM users WHERE email = ?", [$email]);
        if (!$user || !$user['two_factor_enabled']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => '2FA not enabled for this account']);
            exit;
        }
        
        // Check if email method is available
        if ($user['two_factor_method'] !== 'email' && $user['two_factor_method'] !== 'both') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Email 2FA not available for this account']);
            exit;
        }
        
        // Rate limit: max 3 emails per 10 minutes per user
        $rateLimitKey = '2fa_login_email_' . $user['id'];
        if (!TwoFactorAuth::checkRateLimit($rateLimitKey, 3, 600)) {
            http_response_code(429);
            echo json_encode(['success' => false, 'error' => 'Too many requests. Please wait before requesting another code.']);
            exit;
        }
        
        $code = TwoFactorAuth::generateEmailCode();
        $expiresAt = date('Y-m-d H:i:s', time() + 600); // 10 minutes
        
        // Store code temporarily
        $stmt = $pdo->prepare("
            UPDATE users SET 
                two_factor_email_code = ?,
                two_factor_email_code_expires = ?
            WHERE id = ?
        ");
        $stmt->execute([$code, $expiresAt, $user['id']]);
        
        // Send email
        $emailSent = TwoFactorAuth::sendEmailCode($user['email'], $code);
        
        if (!$emailSent) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to send email code']);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Verification code sent to your email'
        ]);
        break;
        
    case 'verify_login_code':
        // Verify 2FA code during login (no auth required yet)
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }
        
        // Use already parsed JSON payload
        $payload = $jsonPayload ?? [];
        
        $email = $payload['email'] ?? '';
        $code = $payload['code'] ?? '';
        $methodChoice = $payload['method'] ?? 'totp'; // 'totp', 'email', or 'backup'
        
        if (empty($email) || empty($code)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Email and code required']);
            exit;
        }
        
        // Find user
        $user = fetchOne("
            SELECT id, two_factor_enabled, two_factor_method, two_factor_secret, 
                   two_factor_email_code, two_factor_email_code_expires, two_factor_backup_codes
            FROM users WHERE email = ?
        ", [$email]);
        
        if (!$user || !$user['two_factor_enabled']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => '2FA not enabled for this account']);
            exit;
        }
        
        // Rate limit: max 5 attempts per 5 minutes
        $rateLimitKey = '2fa_login_attempts_' . $user['id'];
        if (!TwoFactorAuth::checkRateLimit($rateLimitKey, 5, 300)) {
            http_response_code(429);
            echo json_encode(['success' => false, 'error' => 'Too many failed attempts. Please wait before trying again.']);
            exit;
        }
        
        $codeValid = false;
        
        // Verify based on method
        if ($methodChoice === 'totp') {
            if (empty($user['two_factor_secret'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'TOTP not configured']);
                exit;
            }
            $codeValid = TwoFactorAuth::verifyTOTPCode($user['two_factor_secret'], $code);
        } elseif ($methodChoice === 'email') {
            if (empty($user['two_factor_email_code']) || 
                empty($user['two_factor_email_code_expires']) ||
                strtotime($user['two_factor_email_code_expires']) < time()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Email code expired or not found']);
                exit;
            }
            $codeValid = ($user['two_factor_email_code'] === $code);
            
            // Clear email code after successful verification
            if ($codeValid) {
                $stmt = $pdo->prepare("UPDATE users SET two_factor_email_code = NULL, two_factor_email_code_expires = NULL WHERE id = ?");
                $stmt->execute([$user['id']]);
            }
        } elseif ($methodChoice === 'backup') {
            $codeValid = TwoFactorAuth::verifyBackupCode($user['id'], $code);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid verification method']);
            exit;
        }
        
        if (!$codeValid) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Invalid verification code']);
            exit;
        }
        
        // Code is valid - return success (login.php will handle session creation)
        echo json_encode([
            'success' => true,
            'data' => [
                'user_id' => $user['id']
            ],
            'message' => 'Verification successful'
        ]);
        break;
        
    default:
        if (empty($action)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Action parameter is required']);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action: ' . htmlspecialchars($action)]);
        }
        break;
}

