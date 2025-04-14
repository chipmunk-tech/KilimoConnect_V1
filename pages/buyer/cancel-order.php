<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

// Check if user is logged in and is a buyer
if (!is_logged_in() || $_SESSION['role'] !== 'buyer') {
    set_flash_message('error', 'Please login as a buyer to continue');
    header('Location: ' . SITE_URL . '/pages/login.php');
    exit;
}

// Get order ID from URL
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$buyer_id = $_SESSION['user_id'];

if (!$order_id) {
    set_flash_message('error', 'Invalid order ID');
    header('Location: ' . SITE_URL . '/pages/buyer/orders.php');
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Check if order exists and belongs to the buyer
    $check_sql = "
        SELECT o.*, oi.product_id, oi.quantity
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        WHERE o.id = ? AND o.buyer_id = ? AND o.status = 'pending'
        FOR UPDATE
    ";
    $stmt = $pdo->prepare($check_sql);
    $stmt->execute([$order_id, $buyer_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception('Order not found or cannot be cancelled');
    }

    // Update order status to cancelled
    $update_order_sql = "
        UPDATE orders 
        SET status = 'cancelled',
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ";
    $stmt = $pdo->prepare($update_order_sql);
    $stmt->execute([$order_id]);

    // Restore product stock
    $restore_stock_sql = "
        UPDATE products 
        SET stock_quantity = stock_quantity + ?,
            status = CASE 
                WHEN status = 'out_of_stock' AND stock_quantity + ? > 0 
                THEN 'available' 
                ELSE status 
            END,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ";
    $stmt = $pdo->prepare($restore_stock_sql);
    $stmt->execute([$order['quantity'], $order['quantity'], $order['product_id']]);

    // Commit transaction
    $pdo->commit();

    set_flash_message('success', 'Order cancelled successfully');

} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    error_log("Order cancellation error: " . $e->getMessage());
    set_flash_message('error', 'Failed to cancel order. ' . $e->getMessage());
}

// Redirect back to orders page
header('Location: ' . SITE_URL . '/pages/buyer/orders.php');
exit;
?> 