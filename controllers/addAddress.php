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

    // Get the JWT token from the Authorization header
    $headers = apache_request_headers();
    if (!isset($headers['Authorization'])) {
        http_response_code(401);  
        echo json_encode(['message' => 'Authorization token not found']);
        exit;
    }

    $jwt = str_replace('Bearer ', '', $headers['Authorization']);

        try {
            // Decode the JWT to get user information
            $decoded = JWT::decode($jwt, new Key($_ENV['JWT_SECRET'], 'HS256'));
            $userIdFromToken = $decoded->data->id; // Extract user_id from token
        } catch (Exception $e) {
            http_response_code(401); 
            echo json_encode(['message' => 'Invalid token: ' . $e->getMessage()]);
            exit;
        }

    $validator = new Validator();
    $data = json_decode(file_get_contents("php://input"), true);

        // Ensure that the user_id in the data matches the userId from the JWT token
        if (!isset($data['user_id']) || $data['user_id'] !== $userIdFromToken) {
            http_response_code(403); 
            echo json_encode(['message' => 'User ID does not match the token ID. Access denied.']);
            exit;
        }

    // Validation rules
    $validation = $validator->make($data, [
            'unit_number'    => 'required|max:50',
            'street_address' => 'required|max:50',
            'city'           => 'required|max:100',
            'region'         => 'required|max:100',
            'postal_code'    => 'required|max:20',
            'is_default'     => 'boolean'
    ]);

    // Validate the input data
    $validation->validate();

        if ($validation->fails()) {
            $errors = $validation->errors();
            echo json_encode([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $errors->firstOfAll()
            ]);
            exit();
    }

        $database = new Database();
        $db = $database->connect();
        $address = new Address($db);
        $address->user_id = $userIdFromToken;  
        $address->unit_number = $data['unit_number'];
        $address->street_address = $data['street_address'];
        $address->city = $data['city'];
        $address->region = $data['region'];
        $address->postal_code = $data['postal_code'];
        $address->is_default = isset($data['is_default']) ? $data['is_default'] : 0; // Default to 0 if not set

        // Add the address and send the appropriate response
        if ($address->addAddress()) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Address added successfully'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'User is not yet verified, failed to add address'
            ]);
        }

?>
