<?php

require_once __DIR__ . '/../src/database/database.php';
require_once __DIR__ . '/../src/class/user.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['user_id']) && isset($_FILES['profile_picture'])) {
        $userId = $_POST['user_id'];
        $file = $_FILES['profile_picture'];

        // Initialize the User class and call the upload method
        $database = new Database();
        $db = $database->connect();
        $user = new User($db);
        $result = $user->uploadProfilePicture($userId, $file);
        
        // Check for status instead of success
        if ($result['status'] === 'success') {
            http_response_code(200);
        } else {
            http_response_code(400);
        }
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode(['message' => 'Missing user_id or profile picture']);
    }
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['message' => 'Invalid request method']);
}
?>
