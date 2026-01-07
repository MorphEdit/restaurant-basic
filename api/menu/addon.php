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
            if (!isset($_GET['menu_item_id'])) {
                throw new Exception('Menu item ID is required');
            }

            $stmt = $db->prepare("
                SELECT * FROM menu_addons 
                WHERE menu_item_id = ? 
                ORDER BY name
            ");
            $stmt->execute([$_GET['menu_item_id']]);
            
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
            
            $stmt = $db->prepare("
                INSERT INTO menu_addons (menu_item_id, name, price) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([
                $data['menu_item_id'],
                $data['name'],
                $data['price']
            ]);

            echo json_encode([
                'status' => 'success',
                'message' => 'Add-on added successfully',
                'id' => $db->lastInsertId()
            ]);
            break;

        case 'update':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            $data = json_decode(file_get_contents('php://input'), true);
            
            $stmt = $db->prepare("
                UPDATE menu_addons 
                SET name = ?, price = ? 
                WHERE id = ?
            ");
            $stmt->execute([
                $data['name'],
                $data['price'],
                $data['id']
            ]);

            echo json_encode([
                'status' => 'success',
                'message' => 'Add-on updated successfully'
            ]);
            break;

        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            $data = json_decode(file_get_contents('php://input'), true);
            
            try {
                $db->beginTransaction();
                
                // Delete from order_item_addons first
                $stmt = $db->prepare("DELETE FROM order_item_addons WHERE addon_id = ?");
                $stmt->execute([$data['id']]);
                
                // Delete the addon
                $stmt = $db->prepare("DELETE FROM menu_addons WHERE id = ?");
                $stmt->execute([$data['id']]);
                
                $db->commit();
                
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Add-on deleted successfully'
                ]);
            } catch(Exception $e) {
                $db->rollBack();
                throw new Exception('Error deleting add-on: ' . $e->getMessage());
            }
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