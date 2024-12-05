<?php
require_once __DIR__ . '/../src/class/ShoppingCart.php';
require_once __DIR__ . '/../src/class/Order.php';
require_once __DIR__ . '/../src/Database/database.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validate session data
if (!isset($_SESSION['cart_id'], $_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['message' => 'User not logged in or cart not found.']);
    exit();
}

$db = (new Database())->connect(); // Create a PDO instance
$cart = new ShoppingCart($db);
$order = new Order($db);

$cart->cart_id = $_SESSION['cart_id'];
$user_id = $_SESSION['user_id'];

// Get the input data
$data = json_decode(file_get_contents("php://input"), true);
$payment_method_id = $data['payment_method_id'] ?? null;
$address_id = $data['address_id'] ?? null;

// Validate input data
if (is_null($payment_method_id) || is_null($address_id)) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid payment method or address.']);
    exit();
}

// Proceed to checkout
if ($order->checkout($cart->cart_id, $user_id, $payment_method_id, $address_id)) {
    echo json_encode(['message' => 'Checkout successful']);
} else {
    http_response_code(500);
    echo json_encode(['message' => 'Checkout failed']);
}
?>
