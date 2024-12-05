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
            http_response_code(500); // Internal Server Error
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

            // Check if the user is an admin
            $userObj = new User($db);
            $admin_profile = $userObj->getUserProfile($admin_user_id);

                if ($admin_profile['role'] !== 'admin') {
                    http_response_code(403); // Forbidden
                    echo json_encode(['message' => 'Access denied. Only admins can view users.']);
                    exit();
                }

            // Set default values for pagination
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10; // Default limit
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0; // Default offset

                // Fetch verified users excluding admin with pagination
                $query = "SELECT user_id, first_name, last_name, email, role FROM users 
                        WHERE role IN ('customer', 'vendor') AND is_verified = 1 
                        LIMIT :limit OFFSET :offset";
                $stmt = $db->prepare($query);
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                $stmt->execute();

                $users = [];

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $users[] = $row;
                } if (count($users) > 0) {
                    http_response_code(200); // OK
                    echo json_encode($users);
                } else {
                    http_response_code(404); // Not Found
                    echo json_encode(['message' => 'No verified users found.']);
                }

        } catch (Exception $e) {
            http_response_code(401); // Unauthorized
            echo json_encode(['message' => 'Access denied', 'error' => $e->getMessage()]);
        }