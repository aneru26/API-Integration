<?php
define('LOG_FILE', __DIR__ . '/logs/access.log'); // Path to the log file (adjust this as necessary)

// Function to log API requests
function logRequest($method, $uri, $statusCode) {
    $logEntry = "[" . date('Y-m-d H:i:s') . "] $method $uri - $statusCode\n";
    $result = file_put_contents(LOG_FILE, $logEntry, FILE_APPEND);
    if ($result === false) {
        // Log an error if writing to the log fails
        error_log("Failed to write to log file: " . LOG_FILE);
    }
}

// Function to send JSON response
function sendJsonResponse($statusCode, $data) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

// Function to send error response
function sendErrorResponse($statusCode, $message) {
    sendJsonResponse($statusCode, ['error' => $message]);
}
