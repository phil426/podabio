<?php
/**
 * User Class
 * PodaBio
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/helpers.php';

class User {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDB();
    }
    
    /**
     * Create new user with email
     * @param string $email
     * @param string $password
     * @return array ['success' => bool, 'user_id' => int|null, 'error' => string|null]
     */
    public function create($email, $password) {
        // Validate email
        if (!isValidEmail($email)) {
            return ['success' => false, 'user_id' => null, 'error' => 'Invalid email address'];
        }
        
        // Check if email exists
        $existing = fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
        if ($existing) {
            return ['success' => false, 'user_id' => null, 'error' => 'Email already registered'];
        }
        
        // Validate password
        if (strlen($password) < 8) {
            return ['success' => false, 'user_id' => null, 'error' => 'Password must be at least 8 characters'];
        }
        
        // Hash password
        $passwordHash = hashPassword($password);
        
        // Generate verification token
        $verificationToken = generateToken(32);
        $tokenExpires = date('Y-m-d H:i:s', time() + VERIFICATION_TOKEN_EXPIRY);
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO users (email, password_hash, verification_token, verification_token_expires)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$email, $passwordHash, $verificationToken, $tokenExpires]);
            
            $userId = $this->pdo->lastInsertId();
            
            return [
                'success' => true,
                'user_id' => $userId,
                'verification_token' => $verificationToken,
                'error' => null
            ];
        } catch (PDOException $e) {
            error_log("User creation failed: " . $e->getMessage());
            return ['success' => false, 'user_id' => null, 'error' => 'Failed to create account'];
        }
    }
    
    /**
     * Verify email with token
     * @param string $token
     * @return array ['success' => bool, 'error' => string|null]
     */
    public function verifyEmail($token) {
        $user = fetchOne(
            "SELECT id, verification_token_expires FROM users WHERE verification_token = ? AND email_verified = 0",
            [$token]
        );
        
        if (!$user) {
            return ['success' => false, 'error' => 'Invalid verification token'];
        }
        
        // Check token expiry
        if (strtotime($user['verification_token_expires']) < time()) {
            return ['success' => false, 'error' => 'Verification token has expired'];
        }
        
        // Verify email
        executeQuery(
            "UPDATE users SET email_verified = 1, verification_token = NULL, verification_token_expires = NULL WHERE id = ?",
            [$user['id']]
        );
        
        return ['success' => true, 'error' => null];
    }
    
    /**
     * Login with email and password
     * @param string $email
     * @param string $password
     * @return array ['success' => bool, 'user' => array|null, 'error' => string|null]
     */
    public function login($email, $password) {
        $user = fetchOne("SELECT * FROM users WHERE email = ?", [$email]);
        
        if (!$user) {
            return ['success' => false, 'user' => null, 'error' => 'Invalid email or password'];
        }
        
        // Check password
        if (!verifyPassword($password, $user['password_hash'])) {
            return ['success' => false, 'user' => null, 'error' => 'Invalid email or password'];
        }
        
        // Check if email is verified
        if (!$user['email_verified']) {
            return ['success' => false, 'user' => null, 'error' => 'Email not verified. Please check your email.'];
        }
        
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        regenerateSession();
        
        // Remove password hash from returned user
        unset($user['password_hash']);
        unset($user['verification_token']);
        unset($user['reset_token']);
        
        return ['success' => true, 'user' => $user, 'error' => null];
    }
    
    /**
     * Login with Google ID
     * Note: This method should only be called when user already has Google ID linked
     * or when creating a new account. For linking to existing accounts, use linkGoogleAccount().
     * @param string $googleId
     * @param string $email
     * @param array $profileData
     * @return array ['success' => bool, 'user' => array|null, 'error' => string|null]
     */
    public function loginWithGoogle($googleId, $email, $profileData = []) {
        // Check if user exists with Google ID
        $user = fetchOne("SELECT * FROM users WHERE google_id = ?", [$googleId]);
        
        if ($user) {
            // Update email if changed
            if ($user['email'] !== $email) {
                executeQuery("UPDATE users SET email = ? WHERE id = ?", [$email, $user['id']]);
                $user['email'] = $email;
            }
            
            // Auto-verify email for Google users
            if (!$user['email_verified']) {
                executeQuery("UPDATE users SET email_verified = 1 WHERE id = ?", [$user['id']]);
                $user['email_verified'] = 1;
            }
        } else {
            // Check if email exists with password (but no Google ID)
            // This case is now handled in the callback before calling this method
            $existing = fetchOne("SELECT * FROM users WHERE email = ? AND google_id IS NULL", [$email]);
            
            if ($existing) {
                // This should not happen - callback should redirect to verification
                // But handle gracefully by returning error
                return ['success' => false, 'user' => null, 'error' => 'Account exists. Please verify password to link Google account.'];
            }
            
            // Create new user (no existing account with this email)
            try {
                $stmt = $this->pdo->prepare("
                    INSERT INTO users (email, google_id, email_verified)
                    VALUES (?, ?, 1)
                ");
                $stmt->execute([$email, $googleId]);
                
                $userId = $this->pdo->lastInsertId();
                $user = fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
            } catch (PDOException $e) {
                error_log("Google user creation failed: " . $e->getMessage());
                return ['success' => false, 'user' => null, 'error' => 'Failed to create account'];
            }
        }
        
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        regenerateSession();
        
        // Remove sensitive data
        unset($user['password_hash']);
        unset($user['verification_token']);
        unset($user['reset_token']);
        
        return ['success' => true, 'user' => $user, 'error' => null];
    }
    
    /**
     * Generate password reset token
     * @param string $email
     * @return array ['success' => bool, 'token' => string|null, 'error' => string|null]
     */
    public function generateResetToken($email) {
        $user = fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
        
        if (!$user) {
            // Don't reveal if email exists for security
            return ['success' => true, 'token' => null, 'error' => null];
        }
        
        $resetToken = generateToken(32);
        $tokenExpires = date('Y-m-d H:i:s', time() + RESET_TOKEN_EXPIRY);
        
        executeQuery(
            "UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?",
            [$resetToken, $tokenExpires, $user['id']]
        );
        
        return ['success' => true, 'token' => $resetToken, 'error' => null];
    }
    
    /**
     * Reset password with token
     * @param string $token
     * @param string $newPassword
     * @return array ['success' => bool, 'error' => string|null]
     */
    public function resetPassword($token, $newPassword) {
        $user = fetchOne(
            "SELECT id, reset_token_expires FROM users WHERE reset_token = ?",
            [$token]
        );
        
        if (!$user) {
            return ['success' => false, 'error' => 'Invalid reset token'];
        }
        
        // Check token expiry
        if (strtotime($user['reset_token_expires']) < time()) {
            return ['success' => false, 'error' => 'Reset token has expired'];
        }
        
        // Validate password
        if (strlen($newPassword) < 8) {
            return ['success' => false, 'error' => 'Password must be at least 8 characters'];
        }
        
        // Update password
        $passwordHash = hashPassword($newPassword);
        executeQuery(
            "UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?",
            [$passwordHash, $user['id']]
        );
        
        return ['success' => true, 'error' => null];
    }
    
    /**
     * Get user by ID
     * @param int $userId
     * @return array|null
     */
    public function getById($userId) {
        $user = fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
        if ($user) {
            unset($user['password_hash']);
            unset($user['verification_token']);
            unset($user['reset_token']);
        }
        return $user;
    }
    
    /**
     * Update user profile
     * @param int $userId
     * @param array $data
     * @return bool
     */
    public function update($userId, $data) {
        $allowedFields = ['email'];
        $updates = [];
        $params = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $params[] = $userId;
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
        
        try {
            executeQuery($sql, $params);
            return true;
        } catch (PDOException $e) {
            error_log("User update failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete user account
     * @param int $userId
     * @return bool
     */
    public function delete($userId) {
        try {
            executeQuery("DELETE FROM users WHERE id = ?", [$userId]);
            return true;
        } catch (PDOException $e) {
            error_log("User deletion failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Logout user
     */
    public function logout() {
        destroySession();
    }
    
    /**
     * Link Google account to existing user
     * @param int $userId
     * @param string $googleId
     * @param string $email
     * @return array ['success' => bool, 'error' => string|null]
     */
    public function linkGoogleAccount($userId, $googleId, $email) {
        // Verify user exists and email matches
        $user = fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
        
        if (!$user) {
            return ['success' => false, 'error' => 'User not found'];
        }
        
        // Check if email matches
        if (strtolower($user['email']) !== strtolower($email)) {
            return ['success' => false, 'error' => 'Email mismatch'];
        }
        
        // Check if Google ID already exists for another user
        $existing = fetchOne("SELECT id FROM users WHERE google_id = ? AND id != ?", [$googleId, $userId]);
        if ($existing) {
            return ['success' => false, 'error' => 'Google account already linked to another user'];
        }
        
        try {
            executeQuery(
                "UPDATE users SET google_id = ?, email_verified = 1 WHERE id = ?",
                [$googleId, $userId]
            );
            return ['success' => true, 'error' => null];
        } catch (PDOException $e) {
            error_log("Google account linking failed: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to link Google account'];
        }
    }
    
    /**
     * Unlink Google account from user
     * @param int $userId
     * @return array ['success' => bool, 'error' => string|null]
     */
    public function unlinkGoogleAccount($userId) {
        // Verify user exists
        $user = fetchOne("SELECT password_hash, google_id FROM users WHERE id = ?", [$userId]);
        
        if (!$user) {
            return ['success' => false, 'error' => 'User not found'];
        }
        
        // Check if user has password - must have at least one login method
        if (empty($user['password_hash'])) {
            return ['success' => false, 'error' => 'Cannot unlink Google account. You must have a password set first.'];
        }
        
        if (empty($user['google_id'])) {
            return ['success' => false, 'error' => 'Google account is not linked'];
        }
        
        try {
            executeQuery("UPDATE users SET google_id = NULL WHERE id = ?", [$userId]);
            return ['success' => true, 'error' => null];
        } catch (PDOException $e) {
            error_log("Google account unlinking failed: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to unlink Google account'];
        }
    }
    
    /**
     * Remove password from user account
     * @param int $userId
     * @return array ['success' => bool, 'error' => string|null]
     */
    public function removePassword($userId) {
        // Verify user exists
        $user = fetchOne("SELECT password_hash, google_id FROM users WHERE id = ?", [$userId]);
        
        if (!$user) {
            return ['success' => false, 'error' => 'User not found'];
        }
        
        // Check if user has Google account linked - must have at least one login method
        if (empty($user['google_id'])) {
            return ['success' => false, 'error' => 'Cannot remove password. You must have a Google account linked first.'];
        }
        
        if (empty($user['password_hash'])) {
            return ['success' => false, 'error' => 'Password is not set'];
        }
        
        try {
            executeQuery("UPDATE users SET password_hash = NULL WHERE id = ?", [$userId]);
            return ['success' => true, 'error' => null];
        } catch (PDOException $e) {
            error_log("Password removal failed: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to remove password'];
        }
    }
    
    /**
     * Get account status (login methods available)
     * @param int $userId
     * @return array ['has_password' => bool, 'has_google' => bool, 'methods' => array]
     */
    public function getAccountStatus($userId) {
        $user = fetchOne("SELECT password_hash, google_id FROM users WHERE id = ?", [$userId]);
        
        if (!$user) {
            return ['has_password' => false, 'has_google' => false, 'methods' => []];
        }
        
        $hasPassword = !empty($user['password_hash']);
        $hasGoogle = !empty($user['google_id']);
        
        $methods = [];
        if ($hasPassword) {
            $methods[] = 'password';
        }
        if ($hasGoogle) {
            $methods[] = 'google';
        }
        
        return [
            'has_password' => $hasPassword,
            'has_google' => $hasGoogle,
            'methods' => $methods
        ];
    }
    
    /**
     * Verify password for linking Google account
     * @param int $userId
     * @param string $password
     * @return array ['success' => bool, 'error' => string|null]
     */
    public function verifyPasswordForLinking($userId, $password) {
        $user = fetchOne("SELECT password_hash FROM users WHERE id = ?", [$userId]);
        
        if (!$user) {
            return ['success' => false, 'error' => 'User not found'];
        }
        
        if (empty($user['password_hash'])) {
            return ['success' => false, 'error' => 'No password set for this account'];
        }
        
        if (!verifyPassword($password, $user['password_hash'])) {
            return ['success' => false, 'error' => 'Invalid password'];
        }
        
        return ['success' => true, 'error' => null];
    }
}


