<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

class EmailService {
    private $mail;

    public function __construct() {
        $this->mail = new PHPMailer(true);
        $this->setupMailServer();
    }

    private function setupMailServer() {
        $this->mail->isSMTP();
        $this->mail->Host       = 'smtp.gmail.com';
        $this->mail->SMTPAuth   = true;
        $this->mail->Username   = $_ENV['SMTP_EMAIL'];
        $this->mail->Password   = $_ENV['SMTP_PASSWORD'];
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->Port       = 587;
    }

    public function sendVerificationEmail($toEmail, $tokenId) {
        $this->mail->setFrom('no-reply@example.com', 'IT113 E-Commerce');
        $this->mail->addAddress($toEmail);

        $verificationLink = "http://localhost/CE1-Ecommerce/verification/verifyEmail.php?token=" . $tokenId;

        $this->mail->isHTML(true);
        $this->mail->Subject = 'Verify Your Email Address';
        $this->mail->Body    = "Click the link to verify your email: <a href='$verificationLink'>$verificationLink</a>";

        try {
            $this->mail->send();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    // Method to send password reset request email
    public function sendPasswordResetRequestEmail($toEmail, $resetToken) {
        $this->mail->setFrom('no-reply@example.com', 'IT113 E-Commerce');
        $this->mail->addAddress($toEmail);

        // Generate the reset link with the token
        $resetLink = "http://localhost/user-auth/CE1-Ecommerce/verification/verifyPasswordRequest.php?token=" . $resetToken;

        $this->mail->isHTML(true);
        $this->mail->Subject = 'Password Reset Request';
        $this->mail->Body    = "Click the link to reset your password: <a href='$resetLink'>$resetLink</a>";

        try {
            $this->mail->send();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
