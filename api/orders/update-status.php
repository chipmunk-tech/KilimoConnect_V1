<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Check if user is logged in and is a farmer
if (!is_logged_in() || $_SESSION['role'] !== 'farmer') {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

// Get POST data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate input
if (!isset($data['order_id'], $data['status'], $data['csrf_token'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields'
    ]);
    exit;
}

// Verify CSRF token
if (!verify_csrf_token($data['csrf_token'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid CSRF token'
    ]);
    exit;
}

$order_id = (int)$data['order_id'];
$status = $data['status'];
$farmer_id = $_SESSION['user_id'];

// Validate status
$valid_statuses = ['confirmed', 'shipped', 'delivered', 'cancelled'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid status'
    ]);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Get current order status and details
    $stmt = $pdo->prepare("
        SELECT o.*, oi.quantity, oi.product_id, p.stock_quantity
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        WHERE o.id = ? AND o.farmer_id = ?
        LIMIT 1
    ");
    $stmt->execute([$order_id, $farmer_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception('Order not found or access denied');
    }

    // Validate status transition
    $current_status = $order['status'];
    $valid_transition = false;

    switch ($current_status) {
        case 'pending':
            $valid_transition = in_array($status, ['confirmed', 'cancelled']);
            break;
        case 'confirmed':
            $valid_transition = ($status === 'shipped');
            break;
        case 'shipped':
            $valid_transition = ($status === 'delivered');
            break;
        default:
            $valid_transition = false;
    }

    if (!$valid_transition) {
        throw new Exception('Invalid status transition from ' . $current_status . ' to ' . $status);
    }

    // If confirming order, check stock availability
    if ($status === 'confirmed') {
        if ($order['quantity'] > $order['stock_quantity']) {
            throw new Exception('Insufficient stock quantity');
        }

        // Update product stock
        $stmt = $pdo->prepare("
            UPDATE products 
            SET stock_quantity = stock_quantity - ?
            WHERE id = ?
        ");
        $stmt->execute([$order['quantity'], $order['product_id']]);
    }

    // Update order status
    $stmt = $pdo->prepare("
        UPDATE orders 
        SET status = ?, updated_at = NOW()
        WHERE id = ? AND farmer_id = ?
    ");
    $stmt->execute([$status, $order_id, $farmer_id]);

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Order status updated successfully to ' . ucfirst($status)
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("Error updating order status: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 