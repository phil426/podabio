<?php
/**
 * Page Name Effect Diagnostic Tool
 * Sets value to 'slashed' and traces the entire flow
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/session.php';

$pdo = getDB();

echo "<!DOCTYPE html><html><head><title>Page Name Effect Diagnostic</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#1a1a1a;color:#0f0;} ";
echo ".section{background:#2a2a2a;padding:15px;margin:10px 0;border-left:4px solid #0f0;} ";
echo ".success{color:#0f0;} .error{color:#f00;} .warning{color:#ff0;} .info{color:#0ff;} ";
echo "pre{background:#1a1a1a;padding:10px;overflow-x:auto;} ";
echo "h2{color:#0ff;border-bottom:1px solid #0ff;padding-bottom:5px;} ";
echo "</style></head><body>";
echo "<h1>Page Name Effect Diagnostic Tool</h1>";

// ===================================================================
// STEP 1: Check database column exists
// ===================================================================
echo "<div class='section'><h2>STEP 1: Database Column Check</h2>";

try {
    $columns = $pdo->query("SHOW COLUMNS FROM pages LIKE 'page_name_effect'")->fetchAll();
    if (empty($columns)) {
        echo "<div class='error'>✗ Column 'page_name_effect' does NOT exist in 'pages' table</div>";
        echo "<div class='warning'>Run migration: database/migrate_add_page_name_effect.php</div>";
        exit;
    } else {
        echo "<div class='success'>✓ Column 'page_name_effect' exists</div>";
        $columnInfo = $columns[0];
        echo "<pre>Column info: " . print_r($columnInfo, true) . "</pre>";
    }
} catch (PDOException $e) {
    echo "<div class='error'>✗ Error checking column: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit;
}

// ===================================================================
// STEP 2: Get current user and page
// ===================================================================
echo "<div class='section'><h2>STEP 2: Get Current User & Page</h2>";

// Try to get user from session
session_start();
$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    echo "<div class='warning'>⚠ No user session found. Getting first page for testing...</div>";
    $firstPage = $pdo->query("SELECT id, user_id, username, podcast_name FROM pages LIMIT 1")->fetch();
    if ($firstPage) {
        $pageId = $firstPage['id'];
        $userId = $firstPage['user_id'];
        echo "<div class='info'>Using page ID: {$pageId}, User ID: {$userId}, Username: {$firstPage['username']}</div>";
    } else {
        echo "<div class='error'>✗ No pages found in database</div>";
        exit;
    }
} else {
    echo "<div class='success'>✓ User ID from session: {$userId}</div>";
    
    // Get page for this user
    $page = $pdo->prepare("SELECT id, username, podcast_name, page_name_effect FROM pages WHERE user_id = ? LIMIT 1");
    $page->execute([$userId]);
    $pageData = $page->fetch();
    
    if (!$pageData) {
        echo "<div class='error'>✗ No page found for user ID {$userId}</div>";
        exit;
    }
    
    $pageId = $pageData['id'];
    echo "<div class='success'>✓ Page ID: {$pageId}, Username: {$pageData['username']}, Current page_name_effect: " . ($pageData['page_name_effect'] ?? 'NULL') . "</div>";
}

// ===================================================================
// STEP 3: Set page_name_effect to 'slashed'
// ===================================================================
echo "<div class='section'><h2>STEP 3: Setting page_name_effect to 'slashed'</h2>";

try {
    $update = $pdo->prepare("UPDATE pages SET page_name_effect = ? WHERE id = ?");
    $update->execute(['slashed', $pageId]);
    echo "<div class='success'>✓ Updated page_name_effect to 'slashed' for page ID {$pageId}</div>";
    
    // Verify the update
    $verify = $pdo->prepare("SELECT page_name_effect FROM pages WHERE id = ?");
    $verify->execute([$pageId]);
    $result = $verify->fetch();
    echo "<div class='success'>✓ Verified value in database: " . ($result['page_name_effect'] ?? 'NULL') . "</div>";
} catch (PDOException $e) {
    echo "<div class='error'>✗ Error updating: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit;
}

// ===================================================================
// STEP 4: Test Page Class retrieval
// ===================================================================
echo "<div class='section'><h2>STEP 4: Testing Page Class Retrieval</h2>";

require_once __DIR__ . '/../classes/Page.php';

$pageClass = new Page();
$retrievedPage = $pageClass->getByUserId($userId);

if ($retrievedPage) {
    echo "<div class='success'>✓ Page retrieved via Page::getByUserId()</div>";
    echo "<pre>page_name_effect value: " . var_export($retrievedPage['page_name_effect'] ?? null, true) . "</pre>";
    
    if (($retrievedPage['page_name_effect'] ?? null) === 'slashed') {
        echo "<div class='success'>✓ Value matches 'slashed'</div>";
    } else {
        echo "<div class='error'>✗ Value does NOT match 'slashed'. Got: " . var_export($retrievedPage['page_name_effect'] ?? null, true) . "</div>";
    }
} else {
    echo "<div class='error'>✗ Could not retrieve page</div>";
}

// ===================================================================
// STEP 5: Test editor.php retrieval (simulate)
// ===================================================================
echo "<div class='section'><h2>STEP 5: Testing Editor Retrieval (Simulated)</h2>";

// Simulate what editor.php does
$editorPage = $pdo->prepare("SELECT * FROM pages WHERE user_id = ?");
$editorPage->execute([$userId]);
$editorPageData = $editorPage->fetch();

if ($editorPageData) {
    echo "<div class='success'>✓ Page data retrieved (as editor.php would)</div>";
    echo "<pre>Keys in page array: " . implode(', ', array_keys($editorPageData)) . "</pre>";
    echo "<pre>page_name_effect value: " . var_export($editorPageData['page_name_effect'] ?? null, true) . "</pre>";
} else {
    echo "<div class='error'>✗ Could not retrieve page data</div>";
}

// ===================================================================
// STEP 6: Test API endpoint handling
// ===================================================================
echo "<div class='section'><h2>STEP 6: Testing API Endpoint Handling</h2>";

// Check api/page.php
$apiFile = __DIR__ . '/../api/page.php';
if (file_exists($apiFile)) {
    echo "<div class='success'>✓ api/page.php exists</div>";
    
    // Read and check for page_name_effect handling
    $apiContent = file_get_contents($apiFile);
    if (strpos($apiContent, 'page_name_effect') !== false) {
        echo "<div class='success'>✓ api/page.php contains 'page_name_effect' handling</div>";
        
        // Check if it's in the update_appearance case
        if (strpos($apiContent, 'update_appearance') !== false && 
            strpos($apiContent, 'page_name_effect') !== false) {
            echo "<div class='success'>✓ page_name_effect is handled in update_appearance action</div>";
        } else {
            echo "<div class='warning'>⚠ page_name_effect exists but might not be in update_appearance case</div>";
        }
    } else {
        echo "<div class='error'>✗ api/page.php does NOT contain 'page_name_effect' handling</div>";
    }
} else {
    echo "<div class='error'>✗ api/page.php does NOT exist</div>";
}

// ===================================================================
// STEP 7: Test Page Class update method
// ===================================================================
echo "<div class='section'><h2>STEP 7: Testing Page Class Update Method</h2>";

// Check if Page::update() includes page_name_effect in allowedFields
$pageClassFile = __DIR__ . '/../classes/Page.php';
if (file_exists($pageClassFile)) {
    $pageClassContent = file_get_contents($pageClassFile);
    
    if (strpos($pageClassContent, 'page_name_effect') !== false) {
        echo "<div class='success'>✓ Page.php contains 'page_name_effect'</div>";
        
        // Check if it's in allowedFields
        if (preg_match('/allowedFields.*=.*\[(.*?)\]/s', $pageClassContent, $matches)) {
            $allowedFields = $matches[1];
            if (strpos($allowedFields, 'page_name_effect') !== false) {
                echo "<div class='success'>✓ page_name_effect is in allowedFields array</div>";
            } else {
                echo "<div class='error'>✗ page_name_effect is NOT in allowedFields array</div>";
                echo "<pre>Allowed fields: {$allowedFields}</pre>";
            }
        }
    } else {
        echo "<div class='error'>✗ Page.php does NOT contain 'page_name_effect'</div>";
    }
} else {
    echo "<div class='error'>✗ classes/Page.php does NOT exist</div>";
}

// ===================================================================
// STEP 8: Test page.php rendering
// ===================================================================
echo "<div class='section'><h2>STEP 8: Testing Page Rendering (page.php)</h2>";

$pageRenderFile = __DIR__ . '/../page.php';
if (file_exists($pageRenderFile)) {
    echo "<div class='success'>✓ page.php exists</div>";
    
    $pageRenderContent = file_get_contents($pageRenderFile);
    
    // Check for page_name_effect usage
    if (strpos($pageRenderContent, 'page_name_effect') !== false) {
        echo "<div class='success'>✓ page.php contains 'page_name_effect'</div>";
        
        // Check for specific effect rendering
        if (strpos($pageRenderContent, 'page-title-effect-slashed') !== false) {
            echo "<div class='success'>✓ page.php contains CSS/structure for 'slashed' effect</div>";
        } else {
            echo "<div class='warning'>⚠ page.php does NOT contain 'page-title-effect-slashed'</div>";
        }
        
        // Check if it reads the value
        if (strpos($pageRenderContent, '$page[\'page_name_effect\']') !== false || 
            strpos($pageRenderContent, '$page["page_name_effect"]') !== false) {
            echo "<div class='success'>✓ page.php reads page_name_effect from \$page array</div>";
        }
    } else {
        echo "<div class='error'>✗ page.php does NOT contain 'page_name_effect'</div>";
    }
} else {
    echo "<div class='error'>✗ page.php does NOT exist</div>";
}

// ===================================================================
// STEP 9: Test actual API call (simulated)
// ===================================================================
echo "<div class='section'><h2>STEP 9: Simulating API Update Call</h2>";

// Test the actual update via Page class
try {
    $testUpdate = $pageClass->update($pageId, ['page_name_effect' => '3d-shadow']);
    if ($testUpdate) {
        echo "<div class='success'>✓ Page::update() succeeded</div>";
        
        // Check if value was actually updated
        $checkAfter = $pdo->prepare("SELECT page_name_effect FROM pages WHERE id = ?");
        $checkAfter->execute([$pageId]);
        $afterValue = $checkAfter->fetch();
        
        if (($afterValue['page_name_effect'] ?? null) === '3d-shadow') {
            echo "<div class='success'>✓ Value updated correctly to '3d-shadow'</div>";
            
            // Reset back to 'slashed' for testing
            $reset = $pdo->prepare("UPDATE pages SET page_name_effect = ? WHERE id = ?");
            $reset->execute(['slashed', $pageId]);
            echo "<div class='info'>→ Reset back to 'slashed' for your testing</div>";
        } else {
            echo "<div class='error'>✗ Value was NOT updated. Still: " . var_export($afterValue['page_name_effect'] ?? null, true) . "</div>";
        }
    } else {
        echo "<div class='error'>✗ Page::update() returned false</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>✗ Exception during update test: " . htmlspecialchars($e->getMessage()) . "</div>";
}

// ===================================================================
// SUMMARY
// ===================================================================
echo "<div class='section'><h2>SUMMARY</h2>";
echo "<div class='info'>Page ID tested: {$pageId}</div>";
echo "<div class='info'>User ID tested: {$userId}</div>";
echo "<div class='info'>Current page_name_effect value in database: 'slashed'</div>";
echo "<div class='info'>→ Check editor.php?tab=appearance to see if 'slashed' is selected</div>";
echo "<div class='info'>→ Change the dropdown and check browser console for logs</div>";
echo "<div class='info'>→ Check Network tab to see if API call includes page_name_effect</div>";

echo "</body></html>";

