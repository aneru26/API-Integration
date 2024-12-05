<?php
require_once __DIR__ . '/../src/class/ShoppingCart.php';
require_once __DIR__ . '/../src/Database/database.php';
require_once __DIR__ . '/../initialize.php'; 

$db = new Database();
$conn = $db->connect();
$cart = new ShoppingCart($conn);

if (session_status() == PHP_SESSION_NONE) {
    session_start(); // Start session only if not already started
}

// Debug: Check session data
error_log(print_r($_SESSION, true));

// Check if user_id is set in the session
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['message' => 'User not logged in']);
    exit();
}

if (!isset($_SESSION['cart_id'])) {
    // Generate a new cart_id if it doesn't exist
    $_SESSION['cart_id'] = bin2hex(random_bytes(16));
}

$cart->cart_id = $_SESSION['cart_id'];
$user_id = $_SESSION['user_id'];

// Ensure the cart_id exists in the shopping_cart table
$stmt = $conn->prepare("INSERT IGNORE INTO shopping_cart (cart_id, user_id, created_at) VALUES (?, ?, NOW())");
$stmt->execute([$cart->cart_id, $user_id]);

// Get the input data
$data = json_decode(file_get_contents("php://input"), true);

// Debug: Log the input data
error_log(print_r($data, true));

$product_id = $data['product_id'] ?? null;
$quantity = $data['quantity'] ?? null;

// Validate input
if (!is_numeric($quantity) || $quantity <= 0 || empty($product_id)) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid product ID or quantity.']);
    exit();
}

// Check if the product exists
$stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE product_id = ?");
$stmt->execute([$product_id]);
$productExists = $stmt->fetchColumn();

if (!$productExists) {
    http_response_code(400);
    echo json_encode(['message' => 'Product does not exist.']);
    exit();
}

// Add item to cart
if ($cart->addItem($product_id, $quantity)) {
    echo json_encode(['message' => 'Item added to cart']);
} else {
    http_response_code(500);
    echo json_encode(['message' => 'Failed to add item to cart']);
}
?>
