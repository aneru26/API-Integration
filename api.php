<?php
session_start();

// Check if LOG_FILE is already defined to avoid redefinition
if (!defined('LOG_FILE')) {
    define('LOG_FILE', __DIR__ . '/logs/access.log'); // Path to the log file
}

// Check if CACHE_DIR is already defined to avoid redefinition
if (!defined('CACHE_DIR')) {
    define('CACHE_DIR', realpath(__DIR__ . '/cache')); // Directory to store rate limit and cache files
}

define('CACHE_TIME', 300); // Cache duration in seconds
define('RATE_LIMIT', 100); // Requests per hour

// Headers for CORS and API handling
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-API-KEY");

// Utility Functions

// Validate API keys
function validateApiKey($apiKey) {
    $validApiKeys = ['12345', '67890']; // Replace with your own API keys
    return in_array($apiKey, $validApiKeys);
}

// Enforce rate limiting using file-based system
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

// Log API requests
function logRequest($method, $uri, $statusCode) {
    $logEntry = "[" . date('Y-m-d H:i:s') . "] $method $uri - $statusCode\n";
    file_put_contents(LOG_FILE, $logEntry, FILE_APPEND);
}

// Send JSON response
function sendJsonResponse($statusCode, $data) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

// Send error response
function sendErrorResponse($statusCode, $message) {
    sendJsonResponse($statusCode, ['error' => $message]);
}

// Cache Functions
function getFromCache($key) {
    $cacheFile = CACHE_DIR . md5($key) . '.json';
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < CACHE_TIME) {
        return json_decode(file_get_contents($cacheFile), true);
    }
    return null;
}

function saveToCache($key, $data) {
    $cacheFile = CACHE_DIR . md5($key) . '.json';
    file_put_contents($cacheFile, json_encode($data));
}

// Validate API key
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? null;
if (!$apiKey || !validateApiKey($apiKey)) {
    sendJsonResponse(403, ["error" => "Invalid or missing API key"]);
    logRequest($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], 403);
}

// Enforce rate limiting
if (!rateLimiter($apiKey)) {
    sendJsonResponse(429, ["error" => "Rate limit exceeded"]);
    logRequest($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], 429);
}

// Handle routing
$request_method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];

// Base path for the API routes
$base_path = '/api.php';
$parsed_url = parse_url($request_uri);
$path = $parsed_url['path'];

// Route requests
switch (true) {
    case $path === $base_path . '/register' && $request_method === 'POST':
        require __DIR__ . '/controllers/registerUser.php';
        break;

    case $path === $base_path . '/login' && $request_method === 'POST':
        require __DIR__ . '/controllers/loginUser.php';
        break;

    case $path === $base_path . '/logout' && $request_method === 'POST':
        require __DIR__ . '/controllers/logoutUser.php';
        break;

    case $path === $base_path . '/password/reset/request' && $request_method === 'POST':
        require __DIR__ . '/controllers/passRequest.php';
        break;

    case $path === $base_path . '/password/reset' && $request_method === 'POST':
        require __DIR__ . '/controllers/passReset.php';
        break;

    case $path === $base_path . '/password/change' && $request_method === 'POST':
        require __DIR__ . '/controllers/changePassword.php';
        break;

    case $path === $base_path . '/profile/update' && $request_method === 'POST':
        require __DIR__ . '/controllers/profileUpdate.php';
        break;

    case $path === $base_path . '/user/profile' && $request_method === 'GET':
        require __DIR__ . '/controllers/getUserProfile.php';
        break;

    case $path === $base_path . '/role/assign' && $request_method === 'POST':
        require __DIR__ . '/controllers/assignRole.php';
        break;

    case $path === $base_path . '/role/revoke' && $request_method === 'POST':
        require __DIR__ . '/controllers/revokeUser.php';
        break;

    case $path === $base_path . '/profile/photo/upload' && $request_method === 'POST':
        require __DIR__ . '/controllers/uploadProfilePicture.php';
        break;

    case $path === $base_path . '/address' && $request_method === 'POST':
        require __DIR__ . '/controllers/addAddress.php';
        break;

    case $path === $base_path . '/update/address' && $request_method === 'POST':
        require __DIR__ . '/controllers/updateAddress.php';
        break;

    case $path === $base_path . '/delete/address' && $request_method === 'DELETE':
        require __DIR__ . '/controllers/deleteAddress.php';
        break;

    case preg_match("#^" . $base_path . "/roles/([a-zA-Z0-9]+)$#", $path, $matches) && $request_method === 'GET':
        $user_id = $matches[1]; // Extract the user ID from the URL
        require __DIR__ . '/controllers/getUserRoles.php';
        break;

    case $path === $base_path . "/all/users" && $request_method === 'GET':
        require __DIR__ . '/controllers/listAllUsers.php';
        break;

    case $path === $base_path . '/products/add' && $request_method === 'POST':
        require __DIR__ . '/controllers/addProduct.php';
        break;

    case $path === $base_path . '/products/delete' && $request_method === 'DELETE':
        require __DIR__ . '/controllers/deleteProduct.php';
        break;

    case $path === $base_path . '/products/update' && $request_method === 'PUT':
        require __DIR__ . '/controllers/updateProduct.php';
        break;

    case preg_match("#^" . $base_path . "/products/([0-9]+)$#", $path, $matches) && $request_method === 'GET':
        $product_id = (int)$matches[1];
        require __DIR__ . '/controllers/getProductDetails.php';
        break;

    case $path === $base_path . '/products/search' && $request_method === 'GET':
        $cacheKey = md5($request_uri);
        $cachedResponse = getFromCache($cacheKey);
        if ($cachedResponse) {
            sendJsonResponse(200, $cachedResponse);
            logRequest($request_method, $request_uri, 200);
        }

        ob_start();
        require __DIR__ . '/controllers/searchController.php'; // This should echo the response
        $response = ob_get_clean();
        saveToCache($cacheKey, json_decode($response, true));
        echo $response;
        break;

    case $path === $base_path . '/cart/add' && $request_method === 'POST':
        require __DIR__ . '/controllers/addItemCart.php';
        break;

    case $path === $base_path . '/cart/update' && $request_method === 'PUT':
        require __DIR__ . '/controllers/updateItemQuantity.php';
        break;

    case $path === $base_path . '/cart/checkout' && $request_method === 'POST':
        require __DIR__ . '/controllers/checkout.php';
        break;

    default:
        sendJsonResponse(404, ["message" => "Endpoint not found"]);
        logRequest($request_method, $request_uri, 404);
        break;
}
