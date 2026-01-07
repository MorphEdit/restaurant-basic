<?php
header('Content-Type: application/json');
require_once('../config/database.php');

try {
    $db = getDB();
    
    // Get all categories with their menu items
    $stmt = $db->query("
        SELECT 
            c.id as category_id,
            c.name as category_name,
            c.description as category_description,
            m.id as menu_id,
            m.name as menu_name,
            m.description as menu_description,
            m.price,
            m.image,
            m.status
        FROM categories c
        LEFT JOIN menu_items m ON c.id = m.category_id
        WHERE m.status = 'available' OR m.id IS NULL
        ORDER BY c.name, m.name
    ");
    
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group by category
    $categories = [];
    foreach ($result as $row) {
        $catId = $row['category_id'];
        
        if (!isset($categories[$catId])) {
            $categories[$catId] = [
                'id' => $row['category_id'],
                'name' => $row['category_name'],
                'description' => $row['category_description'],
                'items' => []
            ];
        }
        
        if ($row['menu_id']) {
            $categories[$catId]['items'][] = [
                'id' => $row['menu_id'],
                'name' => $row['menu_name'],
                'description' => $row['menu_description'],
                'price' => $row['price'],
                'image' => $row['image'],
                'status' => $row['status']
            ];
        }
    }
    
    // Get add-ons for all menu items
    $stmt = $db->query("SELECT * FROM menu_addons ORDER BY menu_item_id, name");
    $addons = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $addon) {
        $menuId = $addon['menu_item_id'];
        if (!isset($addons[$menuId])) {
            $addons[$menuId] = [];
        }
        $addons[$menuId][] = [
            'id' => $addon['id'],
            'name' => $addon['name'],
            'price' => $addon['price']
        ];
    }
    
    echo json_encode([
        'status' => 'success',
        'data' => [
            'categories' => array_values($categories),
            'addons' => $addons
        ]
    ]);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>