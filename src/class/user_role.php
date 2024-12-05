<?php
class UserRoles {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getUserRole($user_id) {
        $query = "SELECT role FROM users WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();


        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['role'] : null; // Return the role or null if not found
    }

    public function getUserProfile($user_id) {
        $query = "SELECT role FROM users WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
