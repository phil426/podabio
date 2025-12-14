<?php
/**
 * Submit Contact Form API
 * Handles submissions from the Contact Form widget
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');

// Get POST data
$pageId = $_POST['page_id'] ?? 0;
$visitorName = trim($_POST['name'] ?? '');
$visitorEmail = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');
$widgetId = $_POST['widget_id'] ?? 0;

// Validate inputs
if (empty($pageId) || empty($visitorName) || empty($visitorEmail) || empty($message)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'All fields are required']);
    exit;
}

if (!filter_var($visitorEmail, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid email address']);
    exit;
}

// Get page owner and widget config
try {
    // Get page owner
    $page = fetchOne("SELECT * FROM pages WHERE id = ?", [$pageId]);
    if (!$page) {
        throw new Exception('Page not found');
    }

    // Get user email
    $user = fetchOne("SELECT email FROM users WHERE id = ?", [$page['user_id']]);
    if (!$user) {
        throw new Exception('User not found');
    }

    // Get widget settings
    $widget = fetchOne("SELECT config_data FROM page_widgets WHERE id = ? AND page_id = ?", [$widgetId, $pageId]);
    $config = $widget ? json_decode($widget['config_data'], true) : [];

    // Determine recipient email
    $toEmail = !empty($config['email_to']) ? $config['email_to'] : $user['email'];

    // Determine subject
    $subjectPrefix = !empty($config['subject_prefix']) ? $config['subject_prefix'] : 'New Contact from PodaBio';
    $subject = $subjectPrefix . ': ' . $visitorName;

    // Build email body
    $body = "You have received a new message from your PodaBio page contact form.\n\n";
    $body .= "Name: " . $visitorName . "\n";
    $body .= "Email: " . $visitorEmail . "\n";
    $body .= "Date: " . date('Y-m-d H:i:s') . "\n\n";
    $body .= "Message:\n" . $message . "\n\n";
    $body .= "-----------------------------------\n";
    $body .= "Sent via PodaBio Contact Widget";

    // Send email using standard PHP mail() for now
    // In production, should use SMTP or a service like SendGrid/SES
    $headers = "From: PodaBio <no-reply@poda.bio>\r\n";
    $headers .= "Reply-To: " . $visitorEmail . "\r\n";
    $headers .= "X-Mailer: PodaBio/1.0";

    if (mail($toEmail, $subject, $body, $headers)) {
        echo json_encode(['success' => true, 'message' => $config['success_message'] ?? 'Message sent successfully!']);
    } else {
        throw new Exception('Failed to send email');
    }

} catch (Exception $e) {
    error_log("Contact Form Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to send message. Please try again later.']);
}
