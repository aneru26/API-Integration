<?php
class ShoppingCart {
    private $db;
    public $cart_id;

    public function __construct($db) {
        $this->db = $db;
    }

    public function addItem($product_id, $quantity) {
        // Check if the cart_id exists in the shopping_cart table
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM shopping_cart WHERE cart_id = ?");
        $stmt->execute([$this->cart_id]);
        $cartExists = $stmt->fetchColumn();

        // If the cart_id does not exist, insert it
        if (!$cartExists) {
            $stmt = $this->db->prepare("INSERT INTO shopping_cart (cart_id, user_id, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$this->cart_id, $_SESSION['user_id'] ?? null]);
        }

        // Add item to the shopping_cart_item table
        $stmt = $this->db->prepare("INSERT INTO shopping_cart_item (cart_id, product_id, quantity) VALUES (?, ?, ?)");
        return $stmt->execute([$this->cart_id, $product_id, $quantity]);
    }

    public function updateItem($product_id, $quantity) {
        $stmt = $this->db->prepare("UPDATE shopping_cart_item SET quantity = ? WHERE cart_id = ? AND product_id = ?");
        return $stmt->execute([$quantity, $this->cart_id, $product_id]);
    }
}
?>