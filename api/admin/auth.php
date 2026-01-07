<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $data = json_decode(file_get_contents("php://input"));
        
        if (!isset($data->username) || !isset($data->password)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'กรุณากรอก username และ password'
            ]);
            exit;
        }

        $db = getDB();
        
        // Check if admins table exists, if not create it
        $tableCheck = $db->query("SHOW TABLES LIKE 'admins'");
        if ($tableCheck->rowCount() == 0) {
            $db->exec("
                CREATE TABLE admins (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    username VARCHAR(50) NOT NULL UNIQUE,
                    password VARCHAR(50) NOT NULL,
                    first_name VARCHAR(100),
                    last_name VARCHAR(100),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");
            
            // Add default admin
            $db->exec("
                INSERT INTO admins (username, password, first_name, last_name) 
                VALUES ('admin', 'admin123', 'Restaurant', 'Admin')
            ");
        }

        $stmt = $db->prepare("SELECT * FROM admins WHERE username = ? AND password = ?");
        $stmt->execute([$data->username, $data->password]);
        
        if ($user = $stmt->fetch()) {
            echo json_encode([
                'success' => true,
                'message' => 'เข้าสู่ระบบสำเร็จ',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name']
                ]
            ]);
        } else {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง'
            ]);
        }
        
    } catch (Exception $e) {
        error_log($e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
}
?>