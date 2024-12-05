<?php
require_once __DIR__ . '/src/Database/database.php';

$database = new Database();
$conn = $database->connect();

if ($conn) {
    echo "Database connection successful!";
} else {
    echo "Database connection failed!";
}
?>