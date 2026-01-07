<?php
header('Content-Type: application/json');
require_once('../config/database.php');

try {
    $db = getDB();
    
    $stmt = $db->query("SELECT * FROM tables ORDER BY table_number");
    
    echo json_encode([
        'status' => 'success',
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>