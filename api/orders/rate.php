<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please login to continue']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['order_id']) || !isset($input['rating']) || !isset($input['csrf_token'])) {
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
$rating = (int)$input['rating'];
$review = trim($input['review'] ?? '');
$user_id = $_SESSION['user_id'];

// Validate rating
if ($rating < 1 || $rating > 5) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid rating value']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Get order details
    $stmt = $pdo->prepare("
        SELECT o.*, p.farmer_id
        FROM orders o
        LEFT JOIN products p ON o.product_id = p.id
        WHERE o.id = ? AND o.buyer_id = ? AND o.status = 'delivered'
    ");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        throw new Exception('Order not found or cannot be rated');
    }
    
    // Check if order is already rated
    $stmt = $pdo->prepare("
        SELECT id FROM ratings 
        WHERE order_id = ?
    ");
    $stmt->execute([$order_id]);
    if ($stmt->fetch()) {
        throw new Exception('Order has already been rated');
    }
    
    // Add rating
    $stmt = $pdo->prepare("
        INSERT INTO ratings (
            order_id, buyer_id, farmer_id, product_id,
            rating, review, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $order_id,
        $user_id,
        $order['farmer_id'],
        $order['product_id'],
        $rating,
        $review
    ]);
    
    // Update product rating
    $stmt = $pdo->prepare("
        UPDATE products p
        SET rating = (
            SELECT AVG(rating)
            FROM ratings
            WHERE product_id = p.id
        ),
        rating_count = (
            SELECT COUNT(*)
            FROM ratings
            WHERE product_id = p.id
        ),
        updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$order['product_id']]);
    
    // Update farmer rating
    $stmt = $pdo->prepare("
        UPDATE users u
        SET rating = (
            SELECT AVG(rating)
            FROM ratings
            WHERE farmer_id = u.id
        ),
        rating_count = (
            SELECT COUNT(*)
            FROM ratings
            WHERE farmer_id = u.id
        ),
        updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$order['farmer_id']]);
    
    // Send notification to farmer (you can implement this later)
    // send_notification($order['farmer_id'], 'new_rating', $order_id);
    
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Rating submitted successfully']);
    
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 