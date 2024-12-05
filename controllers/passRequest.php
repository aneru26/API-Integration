<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/database/database.php';
require_once __DIR__ . '/../src/password/password_request.php'; 
require_once __DIR__ . '/../src/class/emailservice.php';

use Dotenv\Dotenv;
use Rakit\Validation\Validator;

header('Content-Type: application/json');

        $dotenv = Dotenv::createImmutable(__DIR__ . '/../'); 
        $dotenv->load();

            try {
                $database = new Database();
                $db = $database->connect();
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Database connection failed: ' . $e->getMessage()]);
                exit;
            }

        $data = json_decode(file_get_contents("php://input"), true);

        // Validate the request
        $validator = new Validator;
        $validation = $validator->make($data, [
                    'email' => 'required|email'
                ]);

        $validation->validate();

            if ($validation->fails()) {
                http_response_code(400);
                echo json_encode(['message' => 'Invalid email address.', 'errors' => $validation->errors()->firstOfAll()]);
                exit;
            }

        $email = $data['email'];

        // email and password reset services
        $emailService = new EmailService();
        $passwordResetRequest = new PasswordResetRequest($db, $emailService);

        // Request password reset
        $result = $passwordResetRequest->requestReset($email);

            if ($result['success']) {
                http_response_code(200);
                echo json_encode(['message' => 'Password reset link sent to email']);
            } else {
                http_response_code(500);
                echo json_encode(['message' => $result['message']]);
            }

?>