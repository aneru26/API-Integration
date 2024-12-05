<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/database/database.php';
require_once __DIR__ . '/../src/password/password_reset.php';


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

        // Check if the required keys are present in the request
        $requiredKeys = ['token', 'password', 'confirm_password'];

        foreach ($requiredKeys as $key) {
            if (!isset($data[$key])) {
                http_response_code(400);
                echo json_encode(['message' => 'Missing required field: ' . $key]);
                exit;
            }
        }

        $validator = new Validator;
        $validation = $validator->make($data, [
            'token'            => 'required',
            'password'         => 'required|min:8|regex:/[A-Za-z]/|regex:/[0-9]/|regex:/[@$!%*?&]/',
            'confirm_password' => 'required|same:password' // Ensure confirm_password matches password
        ]);

        try {
        
            $validation->validate();

            // Check for validation errors
            if ($validation->fails()) {
                http_response_code(400);
                echo json_encode(['message' => 'Validation failed', 'errors' => $validation->errors()->firstOfAll()]);
                exit;
            }

            $token = $data['token'];
            $newPassword = $data['password']; // Password to be reset

            // Create password reset service
            $passwordReset = new PasswordReset($db, null); 

            // Reset the password
            $result = $passwordReset->resetPassword($token, $newPassword);

            if ($result['success']) {
                http_response_code(200);
                echo json_encode(['message' => 'Password has been reset successfully']);
            } else {
                http_response_code(400);
                echo json_encode(['message' => $result['message']]);
            }

        } catch (Exception $e) {
            // General error handling
            http_response_code(500);
            echo json_encode(['message' => 'An error occurred: ' . $e->getMessage()]);
        }
