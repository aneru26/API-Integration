<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/database/database.php';
require_once __DIR__ . '/../src/class/address.php';

use Dotenv\Dotenv;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Rakit\Validation\Validator;

header('Content-Type: application/json');


    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();

    // Establish a database connection
    try {
        $database = new Database();
        $db = $database->connect();
        $address = new Address($db);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Database connection failed: ' . $e->getMessage()]);
        exit;
    }

    // Retrieve JWT from Authorization header
    $headers = apache_request_headers();
        if (!isset($headers['Authorization'])) {
            http_response_code(401);
            echo json_encode(['message' => 'Authorization token not found']);
            exit;
        }

    $jwt = str_replace('Bearer ', '', $headers['Authorization']);

        // Decode the JWT
        try {
            $decoded = JWT::decode($jwt, new Key($_ENV['JWT_SECRET'], 'HS256'));
            $userIdFromToken = $decoded->data->id;
        } catch (Exception $e) {
            http_response_code(401);
            echo json_encode(['message' => 'Invalid token: ' . $e->getMessage()]);
            exit;
        }

    // Get input data
    $data = json_decode(file_get_contents("php://input"), true);
    $validator = new Validator();

    // validation rules
    $validation = $validator->make($data, [
            'address_id'      => 'required',
            'user_id'         => 'required',
            'unit_number'     => 'required|max:50',
            'street_address'  => 'required|max:50',
            'city'            => 'required|max:100',
            'region'          => 'required|max:100',
            'postal_code'     => 'required|max:20',
            'is_default'      => 'boolean',
    ]);

    $validation->validate();

        if ($validation->fails()) {
            http_response_code(400);
            echo json_encode(['message' => 'Validation errors', 'errors' => $validation->errors()->firstOfAll()]);
            exit;
        }

        /// Check if the user ID from the token matches the provided user_id and if the user is verified
        if ($userIdFromToken !== $data['user_id'] || !$address->isUserVerified($userIdFromToken)) {
            http_response_code(403);
            echo json_encode(['message' => 'User ID mismatch or user not verified']);
            exit;
        }

        // Check if the address ID provided belongs to the user
        if (!$address->verifyUserAddress($userIdFromToken, $data['address_id'])) {
            http_response_code(403);
            echo json_encode(['message' => 'Unauthorized access: Address ID does not belong to user']);
            exit;
        }


        // Update the address
        if ($address->updateAddress($data)) {
            http_response_code(200);
            echo json_encode(['message' => 'Address updated successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Failed to update address']);
        }

        // Disable error display for production
        ini_set('display_errors', '0');
        error_reporting(0);
?>