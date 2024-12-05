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
$productData = [
    'product_name' => $data['product_name'] ?? null,
    'description' => $data['description'] ?? null,
    'size' => $data['size'] ?? null,
    'color' => $data['color'] ?? null,
    'price' => $data['price'] ?? null,
    'category_id' => $data['category_id'] ?? null,
    'image_url' => $data['image_url'] ?? null,
];

// Verify product ownership
$stmt = $db->prepare("SELECT user_id FROM products WHERE product_id = :product_id");
$stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
$stmt->execute();
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    http_response_code(404); // Not Found
    echo json_encode(['message' => 'Product not found']);
    exit;
}

if ($product['user_id'] !== $userId) {
    http_response_code(403); // Forbidden
    echo json_encode(['message' => 'You can only update your own products']);
    exit;
}

// Proceed with the update
$productObj = new Product($db);
if ($productObj->updateProduct($productId, $productData)) {
    echo json_encode(['message' => 'Product updated successfully']);
} else {
    http_response_code(404); // Not Found
    echo json_encode(['message' => 'Product not found or not owned by you']);
}