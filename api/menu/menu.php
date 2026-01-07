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
            $stmt = $db->prepare("
                SELECT m.*, c.name as category_name
                FROM menu_items m
                LEFT JOIN categories c ON m.category_id = c.id
                ORDER BY m.category_id, m.name
            ");
            $stmt->execute();
            echo json_encode([
                'status' => 'success',
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ]);
            break;

        case 'add':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            // Handle image upload
            $image_name = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $image = $_FILES['image'];
                $image_extension = pathinfo($image['name'], PATHINFO_EXTENSION);
                $image_name = uniqid() . '.' . $image_extension;
                $upload_path = '../../assets/images/menu/' . $image_name;
                
                if (!move_uploaded_file($image['tmp_name'], $upload_path)) {
                    throw new Exception('Failed to upload image');
                }
            }
            
            $stmt = $db->prepare("
                INSERT INTO menu_items (category_id, name, description, price, status, image) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $_POST['category_id'],
                $_POST['name'],
                $_POST['description'],
                $_POST['price'],
                $_POST['status'] ?? 'available',
                $image_name
            ]);

            $menuId = $db->lastInsertId();
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Menu item added successfully',
                'id' => $menuId
            ]);
            break;

        case 'update':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            $image_name = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $image = $_FILES['image'];
                $image_extension = pathinfo($image['name'], PATHINFO_EXTENSION);
                $image_name = uniqid() . '.' . $image_extension;
                $upload_path = '../../assets/images/menu/' . $image_name;
                
                if (!move_uploaded_file($image['tmp_name'], $upload_path)) {
                    throw new Exception('Failed to upload image');
                }
            }

            $sql = "UPDATE menu_items SET 
                    category_id = ?, 
                    name = ?,
                    description = ?,
                    price = ?,
                    status = ?";
            
            $params = [
                $_POST['category_id'],
                $_POST['name'],
                $_POST['description'],
                $_POST['price'],
                $_POST['status']
            ];

            if ($image_name) {
                $sql .= ", image = ?";
                $params[] = $image_name;
            }

            $sql .= " WHERE id = ?";
            $params[] = $_POST['id'];

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Menu item updated successfully'
            ]);
            break;

        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }
        
            $data = json_decode(file_get_contents('php://input'), true);
            
            try {
                $db->beginTransaction();
        
                // Delete order_item_addons first
                $stmt = $db->prepare("
                    DELETE oia FROM order_item_addons oia
                    INNER JOIN order_items oi ON oia.order_item_id = oi.id
                    WHERE oi.menu_item_id = ?
                ");
                $stmt->execute([$data['id']]);
        
                // Delete order_items
                $stmt = $db->prepare("DELETE FROM order_items WHERE menu_item_id = ?");
                $stmt->execute([$data['id']]);
        
                // Delete menu_addons
                $stmt = $db->prepare("DELETE FROM menu_addons WHERE menu_item_id = ?");
                $stmt->execute([$data['id']]);
        
                // Delete menu_item
                $stmt = $db->prepare("DELETE FROM menu_items WHERE id = ?");
                $stmt->execute([$data['id']]);
        
                $db->commit();
                
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Menu item deleted successfully'
                ]);
            } catch(Exception $e) {
                $db->rollBack();
                throw new Exception('Error deleting menu item: ' . $e->getMessage());
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