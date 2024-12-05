<?php
class PasswordResetRequest {

    private $db;
    private $emailService;

    public function __construct($db, $emailService) {
        $this->db = $db;
        $this->emailService = $emailService;
    }

        public function requestReset($email) {

        // Check if the email exists in the database
        $stmt = $this->db->prepare("SELECT user_id FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);

            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'message' => 'Email not found.'];
            }

        // Fetch user_id
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $userId = $user['user_id'];

        $token = bin2hex(random_bytes(16)); // 16 bytes = 32 characters

        // Store the reset token in the database 
        $insertStmt = $this->db->prepare("
            INSERT INTO password_resets (user_id, token, created_at, expires_at) 
            VALUES (:user_id, :token, NOW(), DATE_ADD(NOW(), INTERVAL 1 HOUR))
        ");

            if (!$insertStmt->execute([
                'user_id' => $userId,
                'token' => $token,
            ])) {
                return ['success' => false, 'message' => 'Failed to store reset token in the database.'];
            }

            // Use the EmailService to send the password reset email
            if ($this->emailService->sendPasswordResetRequestEmail($email, $token)) {
                return ['success' => true, 'message' => 'Password reset link sent to email']; 
            } else {
                return ['success' => false, 'message' => 'Failed to send email.'];
            }
        }
}
