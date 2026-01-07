<?php
header('Content-Type: application/json');
require_once('../config/database.php');

if (!isset($_GET['action'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Action is required']);
    exit();
}

$action = $_GET['action'];

try {
    $db = getDB();
    
    switch($action) {
        case 'list':
            $stmt = $db->query("
                SELECT 
                    t.*,
                    COUNT(CASE WHEN o.status IN ('pending', 'paid') THEN 1 END) as active_orders
                FROM tables t
                LEFT JOIN orders o ON t.id = o.table_id
                GROUP BY t.id
                ORDER BY t.table_number
            ");
            
            echo json_encode([
                'status' => 'success',
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ]);
            break;

        case 'add':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            $data = json_decode(file_get_contents('php://input'), true);
            
            // Check if table number already exists
            $stmt = $db->prepare("SELECT id FROM tables WHERE table_number = ?");
            $stmt->execute([$data['table_number']]);
            if ($stmt->fetch()) {
                throw new Exception('Table number already exists');
            }
            
            // Generate QR code data
            $qrData = uniqid('table_', true);
            
            $stmt = $db->prepare("
                INSERT INTO tables (table_number, seats, status, qr_code) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['table_number'],
                $data['seats'],
                $data['status'] ?? 'available',
                $qrData
            ]);

            echo json_encode([
                'status' => 'success',
                'message' => 'Table added successfully',
                'id' => $db->lastInsertId(),
                'qr_code' => $qrData
            ]);
            break;

        case 'update':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            $data = json_decode(file_get_contents('php://input'), true);
            
            // Check if table number already exists for other tables
            $stmt = $db->prepare("SELECT id FROM tables WHERE table_number = ? AND id != ?");
            $stmt->execute([$data['table_number'], $data['id']]);
            if ($stmt->fetch()) {
                throw new Exception('Table number already exists');
            }
            
            $stmt = $db->prepare("
                UPDATE tables 
                SET table_number = ?, seats = ?, status = ? 
                WHERE id = ?
            ");
            $stmt->execute([
                $data['table_number'],
                $data['seats'],
                $data['status'],
                $data['id']
            ]);

            echo json_encode([
                'status' => 'success',
                'message' => 'Table updated successfully'
            ]);
            break;

        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            $data = json_decode(file_get_contents('php://input'), true);
            
            // Check if table has orders
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM orders WHERE table_id = ?");
            $stmt->execute([$data['id']]);
            $result = $stmt->fetch();
            
            if ($result['count'] > 0) {
                throw new Exception('Cannot delete table with orders');
            }

            $stmt = $db->prepare("DELETE FROM tables WHERE id = ?");
            $stmt->execute([$data['id']]);

            echo json_encode([
                'status' => 'success',
                'message' => 'Table deleted successfully'
            ]);
            break;

        case 'info':
            if (!isset($_GET['qr_code'])) {
                throw new Exception('QR code is required');
            }

            $stmt = $db->prepare("SELECT * FROM tables WHERE qr_code = ?");
            $stmt->execute([$_GET['qr_code']]);
            $table = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$table) {
                throw new Exception('Invalid QR code');
            }

            echo json_encode([
                'status' => 'success',
                'data' => $table
            ]);
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch(Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>