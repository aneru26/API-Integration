<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/database/database.php';
require_once __DIR__ . '/../src/class/product.php';

use Dotenv\Dotenv;

header("Content-Type: application/json");

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

try {
    $database = new Database();
    $db = $database->connect();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

// Instantiate the Product class
$product = new Product($db);

// Get the input data
$data = json_decode(file_get_contents("php://input"), true);
$keyword = $data['keyword'] ?? null;
$page = isset($data['page']) ? (int) $data['page'] : 1;
$category = $data['category'] ?? null;
$sort = $data['sort'] ?? 'ASC'; // Default sorting to ascending

try {
    // Call the search function with pagination, optional category filter, and sorting
    $results = $product->searchProducts($keyword, $page, 5, $category, $sort);

    if (empty($results)) {
        http_response_code(404);
        echo json_encode(['message' => 'No products found.']);
    } else {
        http_response_code(200);
        echo json_encode($results); // Return results as JSON
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Error executing query: ' . $e->getMessage()]);
}