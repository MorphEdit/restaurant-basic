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
            $stmt = $db->query("SELECT * FROM categories ORDER BY name");
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
            
            $stmt = $db->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            $stmt->execute([$data['name'], $data['description'] ?? '']);

            echo json_encode([
                'status' => 'success',
                'message' => 'Category added successfully',
                'id' => $db->lastInsertId()
            ]);
            break;

        case 'update':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            $data = json_decode(file_get_contents('php://input'), true);
            
            $stmt = $db->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
            $stmt->execute([$data['name'], $data['description'] ?? '', $data['id']]);

            echo json_encode([
                'status' => 'success',
                'message' => 'Category updated successfully'
            ]);
            break;

        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            $data = json_decode(file_get_contents('php://input'), true);
            
            // Check if category has menu items
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM menu_items WHERE category_id = ?");
            $stmt->execute([$data['id']]);
            $result = $stmt->fetch();
            
            if ($result['count'] > 0) {
                throw new Exception('Cannot delete category with menu items');
            }

            $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$data['id']]);

            echo json_encode([
                'status' => 'success',
                'message' => 'Category deleted successfully'
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