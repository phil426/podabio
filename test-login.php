<?php
/**
 * Test script to debug login.php 500 error
 */

// Enable error display for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "Step 1: PHP is working<br>";

try {
    echo "Step 2: About to include constants.php<br>";
    require_once __DIR__ . '/config/constants.php';
    echo "Step 3: Constants loaded successfully<br>";
    
    echo "Step 4: About to include session.php<br>";
    require_once __DIR__ . '/includes/session.php';
    echo "Step 5: Session loaded successfully<br>";
    
    echo "Step 6: About to include helpers.php<br>";
    require_once __DIR__ . '/includes/helpers.php';
    echo "Step 7: Helpers loaded successfully<br>";
    
    echo "Step 8: About to include User.php<br>";
    require_once __DIR__ . '/classes/User.php';
    echo "Step 9: User class loaded successfully<br>";
    
    echo "Step 10: About to include TwoFactorAuth.php<br>";
    require_once __DIR__ . '/classes/TwoFactorAuth.php';
    echo "Step 11: TwoFactorAuth class loaded successfully<br>";
    
    echo "Step 12: About to check for oauth.php<br>";
    if (file_exists(__DIR__ . '/config/oauth.php')) {
        echo "Step 13: oauth.php file exists, loading...<br>";
        require_once __DIR__ . '/config/oauth.php';
        echo "Step 14: OAuth config loaded successfully<br>";
    } else {
        echo "Step 13: oauth.php file not found (this is OK)<br>";
    }
    
    echo "<br>✅ ALL FILES LOADED SUCCESSFULLY!<br>";
    
} catch (Throwable $e) {
    echo "<br>❌ ERROR: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

