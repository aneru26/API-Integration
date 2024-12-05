<?php

class Order {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function checkout($cart_id, $user_id, $payment_method_id, $address_id) {
        try {
            // Check if the payment method exists for the current user
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM user_payment_method WHERE user_id = ? AND payment_method_id = ?");
            $stmt->execute([$user_id, $payment_method_id]);
            $paymentMethodExists = $stmt->fetchColumn();
    
            if (!$paymentMethodExists) {
                http_response_code(400);
                echo json_encode(['message' => 'Invalid payment method for the user.']);
                return false;
            }
    
            $this->db->beginTransaction();
    
            // Proceed to insert the order if payment method is valid
            $stmt = $this->db->prepare("INSERT INTO shop_order (order_id, user_id, payment_method_id, address_id, total_amount, order_status, order_date) 
                VALUES (UUID(), ?, ?, ?, ?, 'pending', NOW())");
            $stmt->execute([$user_id, $payment_method_id, $address_id, $this->calculateTotalAmount($cart_id)]);
    
            $order_id = $this->db->lastInsertId();
    
            // Insert order items from shopping_cart_item table
            $stmt = $this->db->prepare("INSERT INTO order_items (order_id, product_id, quantity) 
                SELECT ?, product_id, quantity FROM shopping_cart_item WHERE cart_id = ?");
            $stmt->execute([$order_id, $cart_id]);
    
            // Delete the cart items after checkout
            $stmt = $this->db->prepare("DELETE FROM shopping_cart_item WHERE cart_id = ?");
            $stmt->execute([$cart_id]);
    
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            var_dump($e->getMessage());
            return false;
        }
    }
    
    
    private function calculateTotalAmount($cart_id) {
        $stmt = $this->db->prepare("SELECT SUM(p.price * sci.quantity) AS total_amount FROM shopping_cart_item sci JOIN products p ON sci.product_id = p.product_id WHERE sci.cart_id = ?");
        $stmt->execute([$cart_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
        // Debug: Check if total_amount is calculated
        var_dump($result);
    
        return $result['total_amount'];
    }
    
}
?>