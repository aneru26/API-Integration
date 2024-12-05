<?php
require_once 'src/class/ShoppingCart.php';
$db = new Database(); // Assuming you have a Database class for DB connection
$cart = new ShoppingCart($db);
$cart->cart_id = $_SESSION['cart_id']; // Assuming you store cart_id in session

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