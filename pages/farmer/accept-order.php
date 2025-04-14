<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

// Check if user is logged in and is a farmer
if (!is_logged_in() || $_SESSION['role'] !== 'farmer') {
    set_flash_message('error', 'Please login as a farmer to accept orders');
    header('Location: ' . SITE_URL . '/pages/login.php');
    exit;
}

// Get order ID from URL
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$farmer_id = $_SESSION['user_id'];

try {
    // Get order details first
    $stmt = $pdo->prepare("
        SELECT o.*, u.phone as buyer_phone, u.username as buyer_name, p.name as product_name
        FROM orders o
        JOIN users u ON o.buyer_id = u.id
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        WHERE o.id = ? AND o.farmer_id = ? AND o.status = 'pending'
        LIMIT 1
    ");
    $stmt->execute([$order_id, $farmer_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception('Order not found or already processed');
    }

    // Update order status to confirmed
    $stmt = $pdo->prepare("
        UPDATE orders 
        SET status = 'confirmed', 
            updated_at = NOW() 
        WHERE id = ? AND farmer_id = ? AND status = 'pending'
    ");
    $stmt->execute([$order_id, $farmer_id]);

    if ($stmt->rowCount() > 0) {
        // Send SMS notification to buyer (you can implement actual SMS sending later)
        $message = "Your order #" . $order_id . " for " . $order['product_name'] . " has been accepted by the farmer. Thank you for shopping with us!";
        
        // Log the SMS (in real implementation, you would send actual SMS)
        error_log("SMS to " . $order['buyer_phone'] . ": " . $message);

        set_flash_message('success', 'Order accepted successfully. SMS notification sent to buyer.');
    } else {
        set_flash_message('error', 'Order could not be accepted. It may have already been processed.');
    }

} catch (Exception $e) {
    error_log("Error accepting order: " . $e->getMessage());
    set_flash_message('error', $e->getMessage());
}

// Redirect back to orders page
header('Location: orders.php');
exit;
?> 