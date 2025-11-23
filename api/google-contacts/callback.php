<?php
/**
 * Google Contacts OAuth Callback
 * Handles OAuth callback and fetches contacts
 */

// Suppress any warnings/errors that might output before HTML
error_reporting(E_ALL);
ini_set('display_errors', 0);
ob_start(); // Start output buffering

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../config/oauth.php';

// Clear any output that might have been generated
ob_clean();

header('Content-Type: text/html; charset=utf-8');

$code = $_GET['code'] ?? '';
$state = $_GET['state'] ?? '';
$error = $_GET['error'] ?? '';

// Verify state token
if (empty($state) || !isset($_SESSION['google_contacts_oauth_state']) || $state !== $_SESSION['google_contacts_oauth_state']) {
    echo '<script>
        if (window.opener) {
            window.opener.postMessage({
                type: "GOOGLE_CONTACTS_ERROR",
                error: "Invalid authorization request. Please try again."
            }, "' . getCurrentBaseUrl() . '");
        }
        window.close();
    </script>';
    exit;
}

unset($_SESSION['google_contacts_oauth_state']);

if (!empty($error)) {
    echo '<script>
        if (window.opener) {
            window.opener.postMessage({
                type: "GOOGLE_CONTACTS_ERROR",
                error: "Authorization denied or failed."
            }, "' . getCurrentBaseUrl() . '");
        }
        window.close();
    </script>';
    exit;
}

if (empty($code)) {
    echo '<script>
        if (window.opener) {
            window.opener.postMessage({
                type: "GOOGLE_CONTACTS_ERROR",
                error: "No authorization code received."
            }, "' . getCurrentBaseUrl() . '");
        }
        window.close();
    </script>';
    exit;
}

// Exchange code for access token
// Use getCurrentBaseUrl() to automatically detect localhost vs production
$redirectUri = getCurrentBaseUrl() . '/api/google-contacts/callback.php';
$tokenData = getGoogleAccessToken($code, $redirectUri);

if (!$tokenData || !isset($tokenData['access_token'])) {
    echo '<script>
        if (window.opener) {
            window.opener.postMessage({
                type: "GOOGLE_CONTACTS_ERROR",
                error: "Failed to get access token."
            }, "' . getCurrentBaseUrl() . '");
        }
        window.close();
    </script>';
    exit;
}

$accessToken = $tokenData['access_token'];

// Fetch contacts from Google People API
$contacts = fetchGoogleContacts($accessToken);

if ($contacts === null) {
    // Log error for debugging
    error_log("Google Contacts API: Failed to fetch contacts. Access token: " . substr($accessToken, 0, 20) . "...");
    echo '<script>
        if (window.opener) {
            window.opener.postMessage({
                type: "GOOGLE_CONTACTS_ERROR",
                error: "Failed to fetch contacts from Google. Please check that the People API is enabled in Google Cloud Console."
            }, "*");
        }
        window.close();
    </script>';
    exit;
}

// Format contacts for the widget
$formattedContacts = [];
foreach ($contacts as $contact) {
    $formattedContact = [];
    
    // Get name
    if (isset($contact['names']) && !empty($contact['names'])) {
        $name = $contact['names'][0];
        $formattedContact['name'] = trim(($name['givenName'] ?? '') . ' ' . ($name['familyName'] ?? ''));
        if (empty($formattedContact['name'])) {
            $formattedContact['name'] = $name['displayName'] ?? 'Unnamed';
        }
    } else {
        $formattedContact['name'] = 'Unnamed';
    }
    
    // Get email
    if (isset($contact['emailAddresses']) && !empty($contact['emailAddresses'])) {
        $formattedContact['email'] = $contact['emailAddresses'][0]['value'] ?? null;
    }
    
    // Get phone
    if (isset($contact['phoneNumbers']) && !empty($contact['phoneNumbers'])) {
        $formattedContact['phone'] = $contact['phoneNumbers'][0]['value'] ?? null;
    }
    
    // Get organization (company and title)
    if (isset($contact['organizations']) && !empty($contact['organizations'])) {
        $org = $contact['organizations'][0];
        $formattedContact['company'] = $org['name'] ?? null;
        $formattedContact['title'] = $org['title'] ?? null;
    }
    
    // Get address
    if (isset($contact['addresses']) && !empty($contact['addresses'])) {
        $addr = $contact['addresses'][0];
        $addressParts = array_filter([
            $addr['streetAddress'] ?? '',
            $addr['city'] ?? '',
            $addr['region'] ?? '',
            $addr['postalCode'] ?? '',
            $addr['country'] ?? ''
        ]);
        $formattedContact['address'] = !empty($addressParts) ? implode(', ', $addressParts) : null;
    }
    
    // Get photo
    if (isset($contact['photos']) && !empty($contact['photos'])) {
        $formattedContact['photo'] = $contact['photos'][0]['url'] ?? null;
    }
    
    // Get group memberships
    $groups = [];
    if (isset($contact['memberships']) && !empty($contact['memberships'])) {
        foreach ($contact['memberships'] as $membership) {
            if (isset($membership['contactGroupMembership']['contactGroupResourceName'])) {
                // Extract group name from resource name (format: contactGroups/{groupId})
                $resourceName = $membership['contactGroupMembership']['contactGroupResourceName'];
                $groupId = str_replace('contactGroups/', '', $resourceName);
                $groups[] = $groupId;
            }
        }
    }
    $formattedContact['groups'] = $groups;
    
    // Only add if name is not empty
    if (!empty($formattedContact['name'])) {
        $formattedContacts[] = $formattedContact;
    }
}

// Fetch contact groups to get group names
$groupsData = fetchGoogleContactGroups($accessToken);
$groupNames = [];
if ($groupsData && is_array($groupsData)) {
    foreach ($groupsData as $group) {
        if (isset($group['resourceName']) && isset($group['name'])) {
            $groupId = str_replace('contactGroups/', '', $group['resourceName']);
            $groupNames[$groupId] = $group['name'];
        }
    }
}

// Send contacts back to parent window
// Use "*" as origin to avoid COOP issues, but validate in the receiver
$baseUrl = getCurrentBaseUrl();
echo '<script>
    if (window.opener) {
        window.opener.postMessage({
            type: "GOOGLE_CONTACTS_IMPORTED",
            contacts: ' . json_encode($formattedContacts) . ',
            groupNames: ' . json_encode($groupNames) . ',
            origin: "' . $baseUrl . '"
        }, "*");
    }
    setTimeout(function() { window.close(); }, 100);
</script>';

// Note: getGoogleAccessToken() is now defined in config/oauth.php
// and accepts an optional $redirectUri parameter

/**
 * Fetch contacts from Google People API
 */
function fetchGoogleContacts($accessToken) {
    // Fetch contacts with group memberships
    $url = 'https://people.googleapis.com/v1/people/me/connections?personFields=names,emailAddresses,phoneNumbers,organizations,addresses,photos,memberships&pageSize=2000';
    
    $options = [
        'http' => [
            'header' => "Authorization: Bearer {$accessToken}\r\n" .
                       "Accept: application/json\r\n",
            'method' => 'GET',
            'ignore_errors' => true
        ]
    ];
    
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        $error = error_get_last();
        error_log("Google Contacts API: file_get_contents failed. Error: " . ($error['message'] ?? 'Unknown error'));
        return null;
    }
    
    $data = json_decode($response, true);
    
    // Check for API errors
    if (isset($data['error'])) {
        error_log("Google Contacts API Error: " . json_encode($data['error']));
        return null;
    }
    
    if (!isset($data['connections']) || !is_array($data['connections'])) {
        // Empty contacts list is valid, return empty array
        return [];
    }
    
    return $data['connections'];
}

/**
 * Fetch contact groups from Google People API
 */
function fetchGoogleContactGroups($accessToken) {
    $url = 'https://people.googleapis.com/v1/contactGroups?pageSize=1000';
    
    $options = [
        'http' => [
            'header' => "Authorization: Bearer {$accessToken}\r\n" .
                       "Accept: application/json\r\n",
            'method' => 'GET',
            'ignore_errors' => true
        ]
    ];
    
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        return null;
    }
    
    $data = json_decode($response, true);
    
    // Check for API errors
    if (isset($data['error'])) {
        error_log("Google Contact Groups API Error: " . json_encode($data['error']));
        return null;
    }
    
    if (!isset($data['contactGroups']) || !is_array($data['contactGroups'])) {
        return [];
    }
    
    return $data['contactGroups'];
}

