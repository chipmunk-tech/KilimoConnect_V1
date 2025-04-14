<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

// Check if user is logged in and is a farmer
if (!is_logged_in() || $_SESSION['role'] !== 'farmer') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please login as a farmer to continue']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['order_id']) || !isset($input['tracking_number']) || !isset($input['courier']) || !isset($input['csrf_token'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Verify CSRF token
if (!verify_csrf_token($input['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$order_id = (int)$input['order_id'];
$tracking_number = trim($input['tracking_number']);
$courier = trim($input['courier']);
$farmer_id = $_SESSION['user_id'];

// Validate input
if (empty($tracking_number) || empty($courier)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tracking number and courier are required']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Get order details and verify ownership
    $stmt = $pdo->prepare("
        SELECT o.*, p.name as product_name, u.email as buyer_email
        FROM orders o
        LEFT JOIN products p ON o.product_id = p.id
        LEFT JOIN users u ON o.buyer_id = u.id
        WHERE o.id = ? AND o.farmer_id = ? 
        AND o.status = 'shipped'
    ");
    $stmt->execute([$order_id, $farmer_id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        throw new Exception('Order not found or is not in shipped status');
    }
    
    // Update tracking information
    $stmt = $pdo->prepare("
        UPDATE orders 
        SET tracking_number = ?,
            courier = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$tracking_number, $courier, $order_id]);
    
    // Send email notification to buyer (you can implement this later)
    // $subject = "Tracking Information Added - " . SITE_NAME;
    // $message = "Tracking information has been added to your order #{$order_id}:\n";
    // $message .= "Tracking Number: {$tracking_number}\n";
    // $message .= "Courier: {$courier}";
    // send_email($order['buyer_email'], $subject, $message);
    
    $pdo->commit();
    echo json_encode([
        'success' => true, 
        'message' => 'Tracking information added successfully'
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 