<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/database/database.php';
require_once __DIR__ . '/../src/class/user_role.php'; 

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Dotenv\Dotenv;

header("Content-Type: application/json");

    $dotenv = Dotenv::createImmutable(__DIR__ . '/../'); 
    $dotenv->load();

    $jwt_secret_key = $_ENV['JWT_SECRET'];

        try {
            // Establish a connection to the database
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
            // Decode JWT to get admin user ID
            $decoded = JWT::decode($token, new Key($jwt_secret_key, 'HS256'));
            $admin_user_id = $decoded->data->id;

            // Only allow admins to fetch user roles
            $userRolesObj = new UserRoles($db);
            $admin_profile = $userRolesObj->getUserProfile($admin_user_id);
            
            if ($admin_profile['role'] !== 'admin') {
                http_response_code(403); // Forbidden
                echo json_encode(['message' => 'Access denied. Only admins can view roles.']);
                exit();
            }

            // Validate the user_id that was passed from api.php
            if (!isset($user_id)) {
                http_response_code(400); // Bad Request
                echo json_encode(['message' => 'User ID is required']);
                exit();
            }

            // Fetch the role of the user by user_id
            $userRole = $userRolesObj->getUserRole($user_id); 

            if ($userRole) {
                http_response_code(200); // OK
                echo json_encode([
                    'user_id' => $user_id,
                    'role' => $userRole 
                ]);
            } else {
                http_response_code(404); // Not Found
                echo json_encode(['message' => 'User role not found']);
            }

        } catch (Exception $e) {
            http_response_code(401); // Unauthorized
            echo json_encode(['message' => 'Access denied', 'error' => $e->getMessage()]);
        }