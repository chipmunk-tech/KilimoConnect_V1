<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

// Check if user is logged in and is a buyer
if (!is_logged_in() || $_SESSION['role'] !== 'buyer') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please login as a buyer to continue']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$order_id = isset($data['order_id']) ? (int)$data['order_id'] : 0;
$buyer_id = $_SESSION['user_id'];

if (!$order_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Check if order exists and belongs to the buyer
    $check_sql = "SELECT o.id as order_id, o.status, oi.product_id, oi.quantity 
                  FROM orders o 
                  JOIN order_items oi ON o.id = oi.order_id 
                  WHERE o.id = ? AND o.buyer_id = ? AND o.status = 'pending'";
    $stmt = $pdo->prepare($check_sql);
    $stmt->execute([$order_id, $buyer_id]);
    $order = $stmt->fetch();

    if (!$order) {
        throw new Exception('Order not found or cannot be cancelled');
    }

    // Update order status to cancelled
    $update_sql = "UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE id = ?";
    $stmt = $pdo->prepare($update_sql);
    $stmt->execute([$order_id]);

    // Restore product stock
    $restore_sql = "UPDATE products 
                    SET stock_quantity = stock_quantity + ?,
                        status = CASE 
                            WHEN stock_quantity + ? > 0 THEN 'available' 
                            ELSE status 
                        END,
                        updated_at = NOW()
                    WHERE id = ?";
    $stmt = $pdo->prepare($restore_sql);
    $stmt->execute([$order['quantity'], $order['quantity'], $order['product_id']]);

    // Commit transaction
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Order cancelled successfully']);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Order Cancellation Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 