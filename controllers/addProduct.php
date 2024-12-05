<?php

require_once __DIR__ ."/../src/class/user_role.php";
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
    http_response_code(500);  // Internal Server Error
    echo json_encode(['message' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

$headers = apache_request_headers();
if (!isset($headers['Authorization'])) {
    http_response_code(401);  // Unauthorized
    echo json_encode(['message' => 'Authorization header missing']);
    exit();
}

$authHeader = $headers['Authorization'];
$token = str_replace('Bearer ', '', $authHeader);

try {

    $decoded = JWT::decode($token, new Key($jwt_secret_key, 'HS256'));
    $userId = $decoded->data->id;  // Assuming user_id is in the token

    $stmt = $db->prepare("SELECT role FROM users WHERE user_id = :id");
    $stmt->bindParam(':id', $userId, PDO::PARAM_STR); // Use ':id' to match the query
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result || strtolower($result['role']) !== 'vendor') {
        http_response_code(403); // Forbidden
        echo json_encode(['message' => 'Access denied. Only vendors can add products.']);
        exit();
    }
} catch (Exception $e) {
    http_response_code(401);  // Unauthorized
    echo json_encode(['message' => 'Invalid token', 'error' => $e->getMessage()]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

if (
    empty($data['product_name']) ||
    empty($data['size']) ||
    empty($data['color']) ||
    empty($data['price']) ||
    empty($data['category_id'])
) {
    http_response_code(400); // Bad Request
    echo json_encode(['message' => 'Missing required fields.']);
    exit();
}

$product = [
    'product_name' => $data['product_name'],
    'description' => $data['description'] ?? null,
    'size' => $data['size'],
    'color' => $data['color'],
    'price' => $data['price'],
    'category_id' => $data['category_id'],
    'image_url' => $data['image_url'] ?? null,
    'user_id' => $userId  // Add user_id to the product array
];

$productObj = new Product($db);
$productObj->addProduct($product);

echo json_encode(['message' => 'Product added successfully']);