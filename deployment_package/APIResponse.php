<?php
/**
 * API Response Helper
 * Standardized API response formatting
 * Podn.Bio
 */

class APIResponse {
    
    /**
     * Send success response
     * @param mixed $data Response data (optional)
     * @param string|null $message Success message (optional)
     * @param int $httpCode HTTP status code (default 200)
     * @return string JSON-encoded response
     */
    public static function success($data = null, $message = null, $httpCode = 200) {
        http_response_code($httpCode);
        header('Content-Type: application/json');
        
        $response = ['success' => true];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        if ($message !== null) {
            $response['message'] = $message;
        }
        
        return json_encode($response);
    }
    
    /**
     * Send error response
     * @param string $error Error message
     * @param int $httpCode HTTP status code (default 400)
     * @param mixed $details Additional error details (optional)
     * @return string JSON-encoded response
     */
    public static function error($error, $httpCode = 400, $details = null) {
        http_response_code($httpCode);
        header('Content-Type: application/json');
        
        $response = [
            'success' => false,
            'error' => $error
        ];
        
        if ($details !== null) {
            $response['details'] = $details;
        }
        
        return json_encode($response);
    }
    
    /**
     * Send validation error response
     * @param array $errors Array of validation errors
     * @param int $httpCode HTTP status code (default 422)
     * @return string JSON-encoded response
     */
    public static function validationError($errors, $httpCode = 422) {
        http_response_code($httpCode);
        header('Content-Type: application/json');
        
        return json_encode([
            'success' => false,
            'error' => 'Validation failed',
            'errors' => $errors
        ]);
    }
}

