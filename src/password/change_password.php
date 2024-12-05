<?php
class ChangePassword {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Function to verify the current password
    public function verifyCurrentPassword($user_id, $current_password) {
        $query = "SELECT password FROM users WHERE user_id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify the current password against the stored hashed password
        if ($result && password_verify($current_password, $result['password'])) {
            return true;
        }

        return false; // Current password is incorrect
    }

    // Function to update the password in the database
    public function updatePassword($user_id, $new_password) {
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        $query = "UPDATE users SET password = :new_password WHERE user_id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':new_password', $hashed_password);
        $stmt->bindParam(':user_id', $user_id);

        // Execute the query and check if the update was successful
        if ($stmt->execute()) {
            return true;
        }

        return false; // Failed to update password
    }
}
?>
