<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/database/database.php';
require_once __DIR__ . '/../src/class/product.php';

use Dotenv\Dotenv;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

header("Content-Type: application/json");

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$jwt_secret_key = $_ENV['JWT_SECRET'];

try {
    $database = new Database();
    $db = $database->connect();
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['message' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

$headers = apache_request_headers();
if (!isset($headers['Authorization'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['message' => 'Authorization header missing']);
    exit();
}

$authHeader = $headers['Authorization'];
$token = str_replace('Bearer ', '', $authHeader);

try {
    $decoded = JWT::decode($token, new Key($jwt_secret_key, 'HS256'));
    $userId = $decoded->data->id;

    $stmt = $db->prepare("SELECT role FROM users WHERE user_id = :id");
    $stmt->bindParam(':id', $userId, PDO::PARAM_STR);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result || strtolower($result['role']) !== 'vendor') {
        http_response_code(403); // Forbidden
        echo json_encode(['message' => 'Access denied. Only vendors can delete products.']);
        exit();
    }
} catch (Exception $e) {
    http_response_code(401); // Unauthorized
    echo json_encode(['message' => 'Invalid token', 'error' => $e->getMessage()]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['product_id'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['message' => 'Product ID is required']);
    exit();
}

$productId = $data['product_id'];

// Check if the product belongs to the logged-in vendor
try {
    $stmt = $db->prepare("SELECT user_id FROM products WHERE product_id = :product_id");
    $stmt->bindParam(':product_id', $productId, PDO::PARAM_STR);
    $stmt->execute();

    $productOwner = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$productOwner || $productOwner['user_id'] !== $userId) {
        http_response_code(403); // Forbidden
        echo json_encode(['message' => 'You are not allowed to delete this product.']);
        exit();
    }
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['message' => 'Failed to verify product ownership.', 'error' => $e->getMessage()]);
    exit();
}

// Delete the product
$productObj = new Product($db);

// Call deleteProduct with $productId and $userId
if ($productObj->deleteProduct($productId, $userId)) {
    echo json_encode(['message' => 'Product deleted successfully']);
} else {
    http_response_code(403); // Forbidden
    echo json_encode(['message' => 'You are not allowed to delete this product or product not found']);
}