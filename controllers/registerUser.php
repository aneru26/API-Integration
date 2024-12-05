<?php
require_once 'src/Database/database.php';
require_once 'src/class/register.php';
require_once 'src/class/emailservice.php';
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

$data = json_decode(file_get_contents("php://input"), true);

// Check if the required keys are present in the request
$requiredKeys = ['first_name', 'last_name', 'email', 'password', 'confirm_password', 'date_of_birth'];
foreach ($requiredKeys as $key) {
    if (!isset($data[$key])) {
        http_response_code(400);
        echo json_encode(['message' => 'Missing required field: ' . $key]);
        exit;
    }
}

// Validate the input data
$validator = new Validator;
$validation = $validator->make($data, [
    'first_name' => 'required',
    'last_name' => 'required',
    'email' => 'required|email',
    'password' => 'required|min:6',
    'confirm_password' => 'required|same:password',
    'date_of_birth' => 'required|date'
]);

$validation->validate();

if ($validation->fails()) {
    $errors = $validation->errors();
    http_response_code(400);
    echo json_encode(['message' => 'Validation failed', 'errors' => $errors->firstOfAll()]);
    exit;
}

// Assign a default role if not passed
$role = isset($data['role']) ? $data['role'] : 'user';  // Default to 'user' if no role is provided

// Register the user
$register = new Register($db);
$result = $register->registerUser(
    $data['first_name'],
    $data['last_name'],
    $data['email'],
    $data['password'],
    $data['date_of_birth'],
    $role // Pass the role to the registerUser method
);

if ($result['success']) {
    // Set user_id in session
    $_SESSION['user_id'] = $result['user_id'];
    http_response_code(201);
    echo json_encode(['message' => 'User registered successfully', 'user_id' => $result['user_id'], 'token_id' => $result['token_id']]);
} else {
    http_response_code(500);
    echo json_encode(['message' => $result['message']]);
}
?>
