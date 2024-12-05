<?php
class PasswordReset {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Validate the token
    public function validateToken($token) {
        // Check if the token exists in the password_resets table and is not expired
        $stmt = $this->db->prepare("SELECT * FROM password_resets WHERE token = :token AND expires_at > NOW()");
        $stmt->execute(['token' => $token]);

        return $stmt->rowCount() > 0; // Return true if valid, false otherwise
    }

    // Get response message based on token validity
    public function getResponseMessage($isValidToken) {
        if ($isValidToken) {
            return ['message' => 'Token is valid. You can now reset your password.'];
        } else {
            return ['message' => 'Invalid or expired token.'];
        }
    }
}
?>
