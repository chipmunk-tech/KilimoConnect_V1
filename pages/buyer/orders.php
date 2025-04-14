<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

// Check if user is logged in and is a buyer
if (!is_logged_in() || $_SESSION['role'] !== 'buyer') {
    set_flash_message('error', 'Please login as a buyer to continue');
    header('Location: ' . SITE_URL . '/pages/login.php');
    exit;
}

$page_title = "My Orders - " . SITE_NAME;
require_once __DIR__ . '/../../includes/header-buyer.php';

$buyer_id = $_SESSION['user_id'];

try {
    // Get all orders for the buyer with product details
    $orders_sql = "
        SELECT 
            o.*,
            oi.quantity,
            oi.price as item_price,
            p.name as product_name,
            p.id as product_id,
            pi.image_path as product_image,
            u.username as farmer_name,
            u.phone as farmer_phone,
            u.email as farmer_email
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        LEFT JOIN users u ON o.farmer_id = u.id
        WHERE o.buyer_id = ?
        ORDER BY o.created_at DESC
    ";
    $stmt = $pdo->prepare($orders_sql);
    $stmt->execute([$buyer_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Orders Error: " . $e->getMessage());
    set_flash_message('error', 'Unable to fetch orders');
    $orders = [];
}
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2>My Orders</h2>
        </div>
    </div>

    <?php if (empty($orders)): ?>
        <div class="alert alert-info">
            You haven't placed any orders yet. <a href="<?php echo SITE_URL; ?>/pages/products.php">Browse products</a> to place your first order!
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($orders as $order): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Order #<?php echo $order['id']; ?></h5>
                            <span class="badge bg-<?php 
                                echo match($order['status']) {
                                    'pending' => 'warning',
                                    'confirmed' => 'info',
                                    'shipped' => 'primary',
                                    'delivered' => 'success',
                                    'cancelled' => 'danger',
                                    default => 'secondary'
                                };
                            ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <?php if ($order['product_image']): ?>
                                <img src="<?php echo SITE_URL; ?>/uploads/products/<?php echo htmlspecialchars($order['product_image']); ?>" 
                                     class="img-fluid mb-3" alt="<?php echo htmlspecialchars($order['product_name']); ?>"
                                     style="height: 150px; object-fit: cover;">
                            <?php endif; ?>
                            
                            <h5 class="card-title"><?php echo htmlspecialchars($order['product_name']); ?></h5>
                            <p class="card-text">
                                <strong>Quantity:</strong> <?php echo $order['quantity']; ?><br>
                                <strong>Price per unit:</strong> TSh <?php echo number_format($order['item_price'], 2); ?><br>
                                <strong>Total Amount:</strong> TSh <?php echo number_format($order['total_amount'], 2); ?>
                            </p>
                            
                            <div class="mb-3">
                                <h6>Farmer Information</h6>
                                <p class="mb-1">
                                    <strong>Name:</strong> <?php echo htmlspecialchars($order['farmer_name']); ?><br>
                                    <strong>Contact:</strong> <?php echo htmlspecialchars($order['farmer_phone']); ?>
                                </p>
                            </div>
                            
                            <div class="mb-3">
                                <h6>Delivery Address</h6>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?></p>
                            </div>
                            
                            <div class="mb-3">
                                <h6>Order Timeline</h6>
                                <p class="mb-0">
                                    <strong>Placed:</strong> <?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?><br>
                                    <?php if ($order['updated_at'] !== $order['created_at']): ?>
                                        <strong>Last Updated:</strong> <?php echo date('M d, Y H:i', strtotime($order['updated_at'])); ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <div class="card-footer">
                            <?php if ($order['status'] === 'pending'): ?>
                                <button type="button" class="btn btn-danger btn-sm" 
                                        onclick="cancelOrder(<?php echo $order['id']; ?>)">
                                    Cancel Order
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($order['status'] === 'delivered'): ?>
                                <a href="<?php echo SITE_URL; ?>/pages/product.php?id=<?php echo $order['product_id']; ?>" 
                                   class="btn btn-primary btn-sm">
                                    Order Again
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function cancelOrder(orderId) {
    if (confirm('Are you sure you want to cancel this order?')) {
        fetch('<?php echo SITE_URL; ?>/api/orders/cancel.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                order_id: orderId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Failed to cancel order');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while cancelling the order');
        });
    }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?> 