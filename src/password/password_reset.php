<?php
class PasswordReset {
    private $db;
    private $emailService;

    public function __construct($db, $emailService) {
        $this->db = $db;
        $this->emailService = $emailService;
    }

    public function resetPassword($token, $newPassword) {
        // Verify the token
        $stmt = $this->db->prepare("SELECT user_id FROM password_resets WHERE token = :token AND expires_at > NOW()");
        $stmt->execute(['token' => $token]);

        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'message' => 'Invalid or expired token.'];
        }

        // Fetch user ID
        $resetRecord = $stmt->fetch(PDO::FETCH_ASSOC);
        $userId = $resetRecord['user_id'];

        // Hash the new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update the password and clear the reset token
        $updateStmt = $this->db->prepare("UPDATE users SET password = :password WHERE user_id = :user_id");
        $updateSuccess = $updateStmt->execute(['password' => $hashedPassword, 'user_id' => $userId]);

        if ($updateSuccess) {
            // Optionally, remove the token after a successful reset
            $deleteStmt = $this->db->prepare("DELETE FROM password_resets WHERE token = :token");
            $deleteStmt->execute(['token' => $token]);

            return ['success' => true];
        } else {
            return ['success' => false, 'message' => 'Failed to update the password.'];
        }
    }
}
?>
