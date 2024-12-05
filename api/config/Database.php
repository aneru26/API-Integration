<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'it113';
    private $username = 'root';
    private $password = '';
    private $conn;

    // DB connect
    public function connect() {
        $this->conn = null;
        try {
            $pdo = new PDO('mysql:host=' . $this->host, $this->username, $this->password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create the database if it doesn't exist
            $pdo->exec("CREATE DATABASE IF NOT EXISTS " . $this->db_name);
            
            // Connect to the newly created (or existing) database
            $this->conn = new PDO('mysql:host=' . $this->host . ';dbname=' . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            echo 'Connection Error: ' . $e->getMessage();
        }
        return $this->conn;
    }
    
}
?>