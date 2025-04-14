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
$product_id = isset($data['product_id']) ? (int)$data['product_id'] : 0;
$buyer_id = $_SESSION['user_id'];

if (!$product_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

try {
    // Check if product exists and is available
    $check_sql = "SELECT id FROM products WHERE id = ? AND status != 'hidden'";
    $stmt = $pdo->prepare($check_sql);
    $stmt->execute([$product_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Product not found');
    }

    // Check if product is already in wishlist
    $check_wishlist_sql = "SELECT id FROM wishlist WHERE buyer_id = ? AND product_id = ?";
    $stmt = $pdo->prepare($check_wishlist_sql);
    $stmt->execute([$buyer_id, $product_id]);
    $wishlist_item = $stmt->fetch();

    if ($wishlist_item) {
        // Remove from wishlist
        $delete_sql = "DELETE FROM wishlist WHERE buyer_id = ? AND product_id = ?";
        $stmt = $pdo->prepare($delete_sql);
        $stmt->execute([$buyer_id, $product_id]);
        echo json_encode(['success' => true, 'action' => 'removed']);
    } else {
        // Add to wishlist
        $insert_sql = "INSERT INTO wishlist (buyer_id, product_id) VALUES (?, ?)";
        $stmt = $pdo->prepare($insert_sql);
        $stmt->execute([$buyer_id, $product_id]);
        echo json_encode(['success' => true, 'action' => 'added']);
    }

} catch (Exception $e) {
    error_log("Wishlist Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 