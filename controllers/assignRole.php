<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/database/database.php';
require_once __DIR__ . '/../src/class/user.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Dotenv\Dotenv;

header("Content-Type: application/json");

    $dotenv = Dotenv::createImmutable(__DIR__ . '/../'); 
    $dotenv->load();

    $jwt_secret_key = $_ENV['JWT_SECRET'];

        try {

            $database = new Database();
            $db = $database->connect();

        } catch (Exception $e) {
            http_response_code(500);  // Internal Server Error
            echo json_encode(['message' => 'Database connection failed: ' . $e->getMessage()]);
            exit();
        }

    // Check for Authorization header
    $headers = apache_request_headers();
        if (!isset($headers['Authorization'])) {
            http_response_code(401);
            echo json_encode(['message' => 'Authorization header missing']);
            exit();
        }

    $authHeader = $headers['Authorization'];
    $token = str_replace('Bearer ', '', $authHeader);

        try {
            // Decode JWT to get user ID
            $decoded = JWT::decode($token, new Key($jwt_secret_key, 'HS256'));
            $admin_user_id = $decoded->data->id;

            // Only allow admins to assign roles
            $user = new User($db);
            $admin_profile = $user->getProfile($admin_user_id);

            if (empty($admin_profile) || $admin_profile['role'] !== 'admin') {
                http_response_code(403); // Forbidden
                echo json_encode(['message' => 'Access denied. Only admins can assign roles.']);
                exit();
            }

            // Get the input data
            $data = json_decode(file_get_contents("php://input"), true);
            $user_id = $data['user_id'] ?? null;
            $role = $data['role'] ?? null;

            if (!$user_id || !$role) {
                http_response_code(400); // Bad Request
                echo json_encode(['message' => 'User ID and role are required']);
                exit();
            }

            // Assign the role to the user
            $result = $user->assignRole($user_id, $role);

            if ($result['status'] === 'success') {
                http_response_code(200); // OK
                echo json_encode(['message' => $result['message']]);
            } else {
                http_response_code(500); // Internal Server Error
                echo json_encode(['message' => $result['message']]);
            }

            } catch (Exception $e) {
                http_response_code(401); // Unauthorized
                echo json_encode(['message' => 'Access denied', 'error' => $e->getMessage()]);
            }