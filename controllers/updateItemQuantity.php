<?php
require_once 'src/class/ShoppingCart.php';
require_once __DIR__ . '/../src/Database/database.php';

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = new Database(); // Create an instance of Database
$conn = $db->connect(); // Get the PDO connection
$cart = new ShoppingCart($conn); // Pass the PDO connection to ShoppingCart

if (!isset($_SESSION['cart_id'])) {
    http_response_code(400);
    echo json_encode(['message' => 'Cart ID not found in session.']);
    exit();
}

$cart->cart_id = $_SESSION['cart_id']; // Set cart_id in ShoppingCart instance

// Get the input data
$data = json_decode(file_get_contents("php://input"), true);
$product_id = $data['product_id'] ?? null;
$new_quantity = $data['quantity'] ?? null;

// Validate input
if (is_null($product_id) || is_null($new_quantity) || $new_quantity < 0) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid product ID or quantity.']);
    exit();
}

// Update item quantity
if ($cart->updateItem($product_id, $new_quantity)) {
    echo json_encode(['message' => 'Item quantity updated']);
} else {
    http_response_code(500);
    echo json_encode(['message' => 'Failed to update item quantity']);
}
?>
