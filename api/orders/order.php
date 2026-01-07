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
                    o.*,
                    t.table_number,
                    COUNT(oi.id) as item_count
                FROM orders o
                LEFT JOIN tables t ON o.table_id = t.id
                LEFT JOIN order_items oi ON o.id = oi.order_id
                GROUP BY o.id
                ORDER BY o.created_at DESC
            ");
            
            echo json_encode([
                'status' => 'success',
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ]);
            break;

        case 'details':
            if (!isset($_GET['id'])) {
                throw new Exception('Order ID is required');
            }

            // Get order info
            $stmt = $db->prepare("
                SELECT 
                    o.*,
                    t.table_number
                FROM orders o
                LEFT JOIN tables t ON o.table_id = t.id
                WHERE o.id = ?
            ");
            $stmt->execute([$_GET['id']]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                throw new Exception('Order not found');
            }

            // Get order items
            $stmt = $db->prepare("
                SELECT 
                    oi.*,
                    m.name as menu_name,
                    m.image
                FROM order_items oi
                LEFT JOIN menu_items m ON oi.menu_item_id = m.id
                WHERE oi.order_id = ?
            ");
            $stmt->execute([$_GET['id']]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get addons for each item
            foreach ($items as &$item) {
                $stmt = $db->prepare("
                    SELECT 
                        oia.*,
                        ma.name as addon_name
                    FROM order_item_addons oia
                    LEFT JOIN menu_addons ma ON oia.addon_id = ma.id
                    WHERE oia.order_item_id = ?
                ");
                $stmt->execute([$item['id']]);
                $item['addons'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            $order['items'] = $items;
            
            echo json_encode([
                'status' => 'success',
                'data' => $order
            ]);
            break;

        case 'create':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            $data = json_decode(file_get_contents('php://input'), true);
            
            $db->beginTransaction();
            
            try {
                // Create order
                $stmt = $db->prepare("
                    INSERT INTO orders (table_id, total_amount, status, payment_method) 
                    VALUES (?, ?, 'pending', 'promptpay')
                ");
                $stmt->execute([
                    $data['table_id'],
                    $data['total_amount']
                ]);
                
                $orderId = $db->lastInsertId();
                
                // Add order items
                foreach ($data['items'] as $item) {
                    $stmt = $db->prepare("
                        INSERT INTO order_items (order_id, menu_item_id, quantity, price, special_instructions) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $orderId,
                        $item['menu_id'],
                        $item['quantity'],
                        $item['price'],
                        $item['special_instructions'] ?? ''
                    ]);
                    
                    $orderItemId = $db->lastInsertId();
                    
                    // Add addons if any
                    if (!empty($item['addons'])) {
                        foreach ($item['addons'] as $addon) {
                            $stmt = $db->prepare("
                                INSERT INTO order_item_addons (order_item_id, addon_id, price) 
                                VALUES (?, ?, ?)
                            ");
                            $stmt->execute([
                                $orderItemId,
                                $addon['id'],
                                $addon['price']
                            ]);
                        }
                    }
                }
                
                $db->commit();
                
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Order created successfully',
                    'order_id' => $orderId
                ]);
                
            } catch(Exception $e) {
                $db->rollBack();
                throw $e;
            }
            break;

        case 'update_status':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            $data = json_decode(file_get_contents('php://input'), true);
            
            $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$data['status'], $data['id']]);
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Order status updated successfully'
            ]);
            break;

        case 'status':
            if (!isset($_GET['id'])) {
                throw new Exception('Order ID is required');
            }

            $stmt = $db->prepare("SELECT status FROM orders WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                throw new Exception('Order not found');
            }

            echo json_encode([
                'status' => 'success',
                'data' => $result
            ]);
            break;

        case 'stats':
            // Today's stats
            $stmt = $db->query("
                SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                    SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_orders,
                    SUM(CASE WHEN status = 'served' THEN 1 ELSE 0 END) as served_orders,
                    COALESCE(SUM(CASE WHEN status IN ('paid', 'served') THEN total_amount ELSE 0 END), 0) as total_revenue
                FROM orders
                WHERE DATE(created_at) = CURDATE()
            ");
            
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'status' => 'success',
                'data' => $stats
            ]);
            break;

        case 'reports':
            $period = $_GET['period'] ?? 'today';
            
            $dateCondition = "DATE(o.created_at) = CURDATE()";
            if ($period === 'week') {
                $dateCondition = "YEARWEEK(o.created_at, 1) = YEARWEEK(CURDATE(), 1)";
            } elseif ($period === 'month') {
                $dateCondition = "YEAR(o.created_at) = YEAR(CURDATE()) AND MONTH(o.created_at) = MONTH(CURDATE())";
            }
            
            // Sales summary
            $stmt = $db->query("
                SELECT 
                    COUNT(*) as total_orders,
                    COALESCE(SUM(CASE WHEN status IN ('paid', 'served') THEN total_amount ELSE 0 END), 0) as total_revenue,
                    COALESCE(AVG(CASE WHEN status IN ('paid', 'served') THEN total_amount ELSE NULL END), 0) as avg_order_value
                FROM orders o
                WHERE $dateCondition
            ");
            $summary = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Top items
            $stmt = $db->query("
                SELECT 
                    m.name,
                    SUM(oi.quantity) as total_quantity,
                    SUM(oi.quantity * oi.price) as total_revenue
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                JOIN menu_items m ON oi.menu_item_id = m.id
                WHERE $dateCondition AND o.status IN ('paid', 'served')
                GROUP BY oi.menu_item_id
                ORDER BY total_quantity DESC
                LIMIT 10
            ");
            $topItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Recent orders
            $stmt = $db->query("
                SELECT 
                    o.id,
                    o.created_at,
                    o.total_amount,
                    o.status,
                    t.table_number
                FROM orders o
                LEFT JOIN tables t ON o.table_id = t.id
                WHERE $dateCondition
                ORDER BY o.created_at DESC
                LIMIT 20
            ");
            $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'status' => 'success',
                'data' => [
                    'summary' => $summary,
                    'top_items' => $topItems,
                    'recent_orders' => $recentOrders
                ]
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