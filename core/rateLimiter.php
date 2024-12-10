<?php
// Check if CACHE_DIR is already defined to avoid redefinition
if (!defined('CACHE_DIR')) {
    define('CACHE_DIR', realpath(__DIR__ . '/../cache')); // Directory to store rate limit files
}

// Function to enforce rate limiting
function rateLimiter($apiKey) {
    $ip = $apiKey; // Use the API key as the unique identifier
    $rateLimitFile = CACHE_DIR . DIRECTORY_SEPARATOR . 'ratelimit_' . md5($ip) . '.json';

    // Check if directory of cache exists, if not, create it
    if (!is_dir(CACHE_DIR)) {
        if (!mkdir(CACHE_DIR, 0777, true) && !is_dir(CACHE_DIR)) {
            error_log("Failed to create cache directory: " . CACHE_DIR);
            http_response_code(500);
            echo json_encode(['error' => 'Internal Server Error']);
            exit();
        }
    }

    $currentTime = time();
    $rateLimit = [
        'requests' => 0,
        'start_time' => $currentTime,
    ];

    // Load rate limit data if it exists
    if (file_exists($rateLimitFile)) {
        $rateLimit = json_decode(file_get_contents($rateLimitFile), true);
    }

    // Reset request count if time window has passed (1 hour)
    $timeWindow = 3600; // 1 hour in seconds
    if ($currentTime - $rateLimit['start_time'] > $timeWindow) {
        $rateLimit['requests'] = 0;
        $rateLimit['start_time'] = $currentTime;
    }

    // Increment request count
    $rateLimit['requests'] += 1;

    // Write the updated rate limit data to the file
    if (file_put_contents($rateLimitFile, json_encode($rateLimit)) === false) {
        error_log("Failed to write to $rateLimitFile");
        http_response_code(500);
        echo json_encode(['error' => 'Internal Server Error']);
        exit();
    }

    // Block if rate limit exceeded
    if ($rateLimit['requests'] > RATE_LIMIT) {
        http_response_code(429);
        echo json_encode(['error' => 'Too many requests']);
        exit();
    }

    return true;
}
