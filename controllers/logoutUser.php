<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/database/database.php';
require_once __DIR__ . '/../src/class/logout.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Dotenv\Dotenv;

header('Content-Type: application/json');

    $dotenv = Dotenv::createImmutable(__DIR__ . '/../'); 
    $dotenv->load();

        try {
            $database = new Database();
            $db = $database->connect();
        } catch (Exception $e) {
            http_response_code(500);  // Internal Server Error
            echo json_encode(['message' => 'Database connection failed: ' . $e->getMessage()]);
            exit;
        }

    // Get the JWT token from the Authorization header
    $headers = apache_request_headers();

        if (!isset($headers['Authorization'])) {
            http_response_code(401);  // Unauthorized
            echo json_encode(['message' => 'Authorization token not found']);
            exit;
        }

    $jwt = str_replace('Bearer ', '', $headers['Authorization']);

        try {
            // Decode the JWT to extract user ID or additional data
            $decoded = JWT::decode($jwt, new Key($_ENV['JWT_SECRET'], 'HS256'));
            $userId = $decoded->data->id;

            // Instantiate the Logout class and process the logout
            $logout = new Logout($db, $jwt);
            $result = $logout->processLogout();

                if ($result['success']) {
                    http_response_code(200);  // OK
                    echo json_encode(['message' => $result['message']]);
                } else {
                    http_response_code(400);  // Bad Request
                    echo json_encode(['message' => $result['message']]);
                }

            } catch (Exception $e) {
                http_response_code(401);  // Unauthorized
                echo json_encode(['message' => 'Invalid token: ' . $e->getMessage()]);
                exit;
            }