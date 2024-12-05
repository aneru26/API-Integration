<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/database/database.php';
require_once __DIR__ . '/../src/class/user.php';


use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Dotenv\Dotenv;

header('Content-Type: application/json');

    $dotenv = Dotenv::createImmutable(__DIR__ . '/../'); 
    $dotenv->load();

    $jwt_secret_key = $_ENV['JWT_SECRET'];

        try {
            // Establish a connection to the database
            $database = new Database();
            $db = $database->connect();
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Database connection failed: ' . $e->getMessage()]);
            exit;
        }


    // Get the Authorization header from the request
    $headers = apache_request_headers();
        if (!isset($headers['Authorization'])) {
            http_response_code(401);
            echo json_encode(['message' => 'Authorization header missing']);
            exit();
        }

    $authHeader = $headers['Authorization'];
    $token = str_replace('Bearer ', '', $authHeader);

        try {
            // Decode the JWT token to validate the user
            $decoded = JWT::decode($token, new Key($jwt_secret_key, 'HS256'));

            // Check if the user is an admin
            if ($decoded->data->role !== 'admin') {
                http_response_code(403); // Forbidden
                echo json_encode(['message' => 'Access denied. Admins only.']);
                exit();
            }
        } catch (Exception $e) {
            http_response_code(401);
            echo json_encode(['message' => 'Access denied', 'error' => $e->getMessage()]);
            exit();
        }

    // Read input JSON data
    $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['user_id'])) {
            http_response_code(400);
            echo json_encode(['message' => 'User ID is required.']);
            exit();
        }

    $userId = $data['user_id'];

    // Initialize the User class and revoke the role
    $user = new User($db); 
    $response = $user->revokeRole($userId);

        if ($response['success']) {
            http_response_code(200);
        } else {
            http_response_code(400);
        }
        echo json_encode($response);
?>
