<?php
require_once 'src/database/database.php';
require_once 'src/class/login.php';
require_once 'src/class/User.php';
require_once __DIR__ . '/../vendor/autoload.php';

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

    // Get the input data
    $data = json_decode(file_get_contents("php://input"), true);

        if (!$data) {
            http_response_code(400);
            echo json_encode(['message' => 'No input data provided.']);
            exit;
        }

    $validator = new Validator;
    $validation = $validator->make($data, [
            'email'    => 'required|email',
            'password' => 'required|min:6'
    ]);

    $validation->validate();

        if ($validation->fails()) {
            $errors = $validation->errors();
            http_response_code(400);
            echo json_encode(['message' => 'Validation failed', 'errors' => $errors->firstOfAll()]);
            exit;
        }


    $login = new Login($db, ['email' => $data['email'], 'password' => $data['password']]);
    $result = $login->loginUser();

        if ($result['success']) {
            // Set user_id in session
            $_SESSION['user_id'] = $result['user_id'];
            
            // Return response with JWT token and user info
            http_response_code(200);
            echo json_encode([
                'message' => $result['message'],
                'token' => $result['token'],
                'user' => [
                    'id' => $result['user_id'],
                    'name' => $result['name'],
                    'email' => $data['email']
                ]
            ]);
            exit;

        } else {
            $responseMessage = $result['message'] === 'User not verified, please check your email first'
                ? $result['message']
                : 'Login failed. Invalid credentials.';
                
            http_response_code(401);
            echo json_encode(['message' => $responseMessage]);
            exit;
        }
