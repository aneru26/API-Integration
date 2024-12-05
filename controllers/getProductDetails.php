<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/database/database.php';
require_once __DIR__ . '/../src/class/product.php';

use Dotenv\Dotenv;

header("Content-Type: application/json");

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

try {
    $database = new Database();
    $db = $database->connect();
    /*     echo json_encode(['message' => 'Database connection successful']); */
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

$product = new Product($db);
$product_details = $product->getProductDetails($product_id);

if ($product_details) {
    echo json_encode($product_details);
} else {
    http_response_code(404);
    echo json_encode(['message' => 'No products found']);
}