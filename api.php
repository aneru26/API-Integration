<?php
session_start();

use Middleware\MiddlewareManager;
use Middleware\AuthMiddleware;
use Middleware\LoggerMiddleware;

require_once __DIR__ . '/middleware/MiddlewareManager.php';
require_once __DIR__ . '/middleware/AuthMiddleware.php';
require_once __DIR__ . '/middleware/LoggerMiddleware.php';

// Headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Initialize MiddlewareManager
$middlewareManager = new MiddlewareManager();
$middlewareManager->addMiddleware(new LoggerMiddleware());
$middlewareManager->addMiddleware(new AuthMiddleware());

// Handle request through middleware
$middlewareManager->handle($_SERVER, function ($request) {
    require_once __DIR__ . '/api.php'; // Main API routes
});

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

$request_method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];

// Base path for the API routes
$base_path = '/api.php';

// Extract the path from the request URI
$parsed_url = parse_url($request_uri);
$path = $parsed_url['path'];

// Register Route
if ($path === $base_path . '/register' && $request_method === 'POST') {
    require __DIR__ . '/controllers/registerUser.php'; 
    exit();
}

// Login Route
if ($path === $base_path . '/login' && $request_method === 'POST') {
    require __DIR__ . '/controllers/loginUser.php'; 
    exit();
}

// Logout Route
if ($path === $base_path . '/logout' && $request_method === 'POST') {
    require __DIR__ . '/controllers/logoutUser.php';
    exit();
}

// Password Reset Request Route
if ($path === $base_path . '/password/reset/request' && $request_method === 'POST') {
    require __DIR__ . '/controllers/passRequest.php';
    exit();
}

// Password Reset Route
if ($path === $base_path . '/password/reset' && $request_method === 'POST') {
    require __DIR__ . '/controllers/passReset.php';
    exit();
}

// Change Password Route
if ($path === $base_path . '/password/change' && $request_method === 'POST') {
    require __DIR__ . '/controllers/changePassword.php';
    exit();
}

// Profile Update Route
if ($path === $base_path . '/profile/update' && $request_method === 'POST') {
    require __DIR__ . '/controllers/profileUpdate.php';
    exit();
}

// Get User Profile Route
if ($path === $base_path . '/user/profile' && $request_method === 'GET') {
    require __DIR__ . '/controllers/getUserProfile.php';
    exit();
}

// Assign Role to User Route
if ($path === $base_path . '/role/assign' && $request_method === 'POST') {
    require __DIR__ . '/controllers/assignRole.php';
    exit();
}

// Revoke Role from User Route
if ($path === $base_path . '/role/revoke' && $request_method === 'POST') {
    require __DIR__ . '/controllers/revokeUser.php';    
    exit();
}

// Profile Photo Upload Route
if ($path === $base_path . '/profile/photo/upload' && $request_method === 'POST') {
    require __DIR__ . '/controllers/uploadProfilePicture.php';
    exit();
}

// Add Address
if ($path === $base_path . '/address' && $request_method === 'POST') {
    require __DIR__ . '/controllers/addAddress.php'; 
    exit();
}

// Update Address
if ($path === $base_path . '/update/address' && $request_method === 'POST') {
    require __DIR__ . '/controllers/updateAddress.php'; 
    exit();
}

// Delete Address
if ($path === $base_path . '/delete/address' && $request_method === 'DELETE') {
    require __DIR__ . '/controllers/deleteAddress.php'; 
    exit();
}

// List Specific User Roles
if (preg_match("#^" . $base_path . "/roles/([a-zA-Z0-9]+)$#", $path, $matches) && $request_method === 'GET') {
    $user_id = $matches[1]; // Extract the user ID from the URL
    require __DIR__ . '/controllers/getUserRoles.php'; 
    exit();
}

// List All Users
if ($path === $base_path . "/all/users" && $request_method === 'GET') {
    require __DIR__ . '/controllers/listAllUsers.php';
    exit();
}


//Add Product

if ($request_uri === $base_path . '/products/add' && $request_method === 'POST') {
    require __DIR__ . '/controllers/addProduct.php';
    exit();
}


if ($request_uri === $base_path . '/products/delete' && $request_method === 'DELETE') {
    require __DIR__ . '/controllers/deleteProduct.php';
    exit();
}


if ($request_uri === $base_path . '/products/update' && $request_method === 'PUT') {
    require __DIR__ . '/controllers/updateProduct.php';
    exit();
}


if (preg_match("#^" . $base_path . "/products/([0-9]+)$#", $request_uri, $matches) && $request_method === 'GET') {
    $product_id = (int)$matches[1];
    require __DIR__ . '/controllers/getProductDetails.php';
    exit();
}

if ($request_uri === $base_path . '/products/search' && $request_method === 'GET') {
    require __DIR__ . '/controllers/searchController.php';
    exit();
}

// Shopping Cart Routes

// Add Item to Cart
if ($path === $base_path . '/cart/add' && $request_method === 'POST') {
    require __DIR__ . '/controllers/addItemCart.php'; 
    exit();
}

// Update Item Quantity
if ($path === $base_path . '/cart/update' && $request_method === 'PUT') {
    require __DIR__ . '/controllers/updateItemQuantity.php'; 
    exit();
}

// Checkout
if ($path === $base_path . '/cart/checkout' && $request_method === 'POST') {
    require __DIR__ . '/controllers/checkout.php'; 
    exit();
}

// If no route matches, return 404
http_response_code(404);
echo json_encode(['message' => 'Endpoint not found']);