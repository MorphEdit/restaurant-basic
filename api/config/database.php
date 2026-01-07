<?php
// database.php - Restaurant System Basic Package

function getDB() {
    // Docker MySQL connection
    $host = 'mysql';  // ชื่อ service ใน docker-compose
    $dbname = 'restaurant_db';
    $username = 'restaurant_user';
    $password = 'restaurant_pass';
    
    try {
        $db = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        
        return $db;
    } catch(PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        throw new Exception("Database connection failed: " . $e->getMessage());
    }
}
?>