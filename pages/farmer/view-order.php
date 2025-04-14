<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

// Check if user is logged in and is a farmer
if (!is_logged_in() || $_SESSION['role'] !== 'farmer') {
    set_flash_message('error', 'Please login as a farmer to view orders');
    header('Location: ' . SITE_URL . '/pages/login.php');
    exit;
}

// Get order ID from URL
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$farmer_id = $_SESSION['user_id'];

if (!$order_id) {
    set_flash_message('error', 'Invalid order ID');
    header('Location: orders.php');
    exit;
}

try {
    // Get order details with product and buyer information
    $order_sql = "
        SELECT 
            o.*,
            oi.quantity,
            oi.price as item_price,
            p.name as product_name,
            p.description as product_description,
            p.unit,
            p.category,
            p.stock_quantity,
            pi.image_path as product_image,
            u.username as buyer_name,
            u.phone as buyer_phone,
            u.email as buyer_email
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        JOIN users u ON o.buyer_id = u.id
        WHERE o.id = ? AND o.farmer_id = ?
        LIMIT 1";

    $stmt = $pdo->prepare($order_sql);
    $stmt->execute([$order_id, $farmer_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        set_flash_message('error', 'Order not found or access denied');
        header('Location: orders.php');
        exit;
    }

    $page_title = "Order #" . $order_id . " - " . SITE_NAME;
    require_once __DIR__ . '/../../includes/header-farmer.php';

} catch (PDOException $e) {
    error_log("Database Error in view-order.php: " . $e->getMessage());
    set_flash_message('error', 'Unable to fetch order details. Please try again later.');
    header('Location: orders.php');
    exit;
}
?>

<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="orders.php">Orders</a></li>
            <li class="breadcrumb-item active">Order #<?php echo $order_id; ?></li>
        </ol>
    </nav>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['flash_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <!-- Order Details -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Order Details</h5>
                    <?php if ($order['status'] === 'pending'): ?>
                        <div class="btn-group">
                            <a href="update-order.php?id=<?php echo $order_id; ?>&status=confirmed" 
                               class="btn btn-success" 
                               onclick="return confirm('Are you sure you want to accept this order?')">
                                <i class="fas fa-check"></i> Accept Order
                            </a>
                            <a href="update-order.php?id=<?php echo $order_id; ?>&status=cancelled" 
                               class="btn btn-danger" 
                               onclick="return confirm('Are you sure you want to reject this order?')">
                                <i class="fas fa-times"></i> Reject
                            </a>
                        </div>
                    <?php elseif ($order['status'] === 'confirmed'): ?>
                        <a href="update-order.php?id=<?php echo $order_id; ?>&status=shipped" 
                           class="btn btn-primary"
                           onclick="return confirm('Are you sure you want to mark this order as shipped?')">
                            <i class="fas fa-truck"></i> Mark as Shipped
                        </a>
                    <?php elseif ($order['status'] === 'shipped'): ?>
                        <a href="update-order.php?id=<?php echo $order_id; ?>&status=delivered" 
                           class="btn btn-success"
                           onclick="return confirm('Are you sure you want to mark this order as delivered?')">
                            <i class="fas fa-box"></i> Mark as Delivered
                        </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6>Order Status</h6>
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
                        <div class="col-md-6">
                            <h6>Order Date</h6>
                            <p><?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6>Delivery Address</h6>
                            <p><?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Total Amount</h6>
                            <h4 class="text-primary">TSh <?php echo number_format($order['total_amount'], 2); ?></h4>
                        </div>
                    </div>

                    <!-- Product Information -->
                    <h6>Product Information</h6>
                    <div class="card mb-3">
                        <div class="row g-0">
                            <div class="col-md-4">
                                <?php if ($order['product_image']): ?>
                                    <img src="<?php echo SITE_URL; ?>/uploads/products/<?php echo htmlspecialchars($order['product_image']); ?>" 
                                         class="img-fluid rounded-start" alt="<?php echo htmlspecialchars($order['product_name']); ?>"
                                         style="height: 200px; width: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <div class="bg-light d-flex align-items-center justify-content-center h-100">
                                        <i class="fas fa-image fa-3x text-muted"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-8">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($order['product_name']); ?></h5>
                                    <p class="card-text">
                                        <span class="badge bg-info"><?php echo htmlspecialchars($order['category']); ?></span>
                                    </p>
                                    <p class="card-text">
                                        <strong>Ordered Quantity:</strong> <?php echo $order['quantity'] . ' ' . htmlspecialchars($order['unit']); ?><br>
                                        <strong>Current Stock:</strong> <?php echo $order['stock_quantity'] . ' ' . htmlspecialchars($order['unit']); ?><br>
                                        <strong>Price per unit:</strong> TSh <?php echo number_format($order['item_price'], 2); ?>
                                    </p>
                                    <p class="card-text">
                                        <small class="text-muted"><?php echo htmlspecialchars($order['product_description']); ?></small>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Buyer Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Buyer Information</h5>
                </div>
                <div class="card-body">
                    <p><i class="fas fa-user"></i> <?php echo htmlspecialchars($order['buyer_name']); ?></p>
                    <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($order['buyer_phone']); ?></p>
                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($order['buyer_email']); ?></p>
                </div>
            </div>

            <!-- Order Timeline -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Order Timeline</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item">
                            <i class="fas fa-shopping-cart text-primary"></i>
                            <p class="mb-0">Order Placed</p>
                            <small class="text-muted"><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></small>
                        </div>
                        <?php if ($order['status'] !== 'pending' && $order['status'] !== 'cancelled'): ?>
                            <div class="timeline-item">
                                <i class="fas fa-check text-success"></i>
                                <p class="mb-0">Order Confirmed</p>
                                <small class="text-muted"><?php echo date('M j, Y g:i A', strtotime($order['updated_at'])); ?></small>
                            </div>
                        <?php endif; ?>
                        <?php if ($order['status'] === 'shipped' || $order['status'] === 'delivered'): ?>
                            <div class="timeline-item">
                                <i class="fas fa-truck text-info"></i>
                                <p class="mb-0">Order Shipped</p>
                                <small class="text-muted"><?php echo date('M j, Y g:i A', strtotime($order['updated_at'])); ?></small>
                            </div>
                        <?php endif; ?>
                        <?php if ($order['status'] === 'delivered'): ?>
                            <div class="timeline-item">
                                <i class="fas fa-box text-success"></i>
                                <p class="mb-0">Order Delivered</p>
                                <small class="text-muted"><?php echo date('M j, Y g:i A', strtotime($order['updated_at'])); ?></small>
                            </div>
                        <?php endif; ?>
                        <?php if ($order['status'] === 'cancelled'): ?>
                            <div class="timeline-item">
                                <i class="fas fa-times text-danger"></i>
                                <p class="mb-0">Order Cancelled</p>
                                <small class="text-muted"><?php echo date('M j, Y g:i A', strtotime($order['updated_at'])); ?></small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding: 0;
    list-style: none;
}

.timeline-item {
    position: relative;
    padding-left: 40px;
    margin-bottom: 20px;
}

.timeline-item:before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: -20px;
    width: 2px;
    background: #e9ecef;
}

.timeline-item:last-child:before {
    display: none;
}

.timeline-item i {
    position: absolute;
    left: 0;
    top: 0;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: #fff;
    border: 2px solid #e9ecef;
    text-align: center;
    line-height: 26px;
}
</style>

<script>
function updateOrderStatus(orderId, status) {
    let confirmMessage = '';
    switch(status) {
        case 'confirmed':
            confirmMessage = 'Are you sure you want to accept this order?';
            break;
        case 'cancelled':
            confirmMessage = 'Are you sure you want to reject this order?';
            break;
        case 'shipped':
            confirmMessage = 'Are you sure you want to mark this order as shipped?';
            break;
        case 'delivered':
            confirmMessage = 'Are you sure you want to mark this order as delivered?';
            break;
        default:
            confirmMessage = 'Are you sure you want to update the order status?';
    }

    if (confirm(confirmMessage)) {
        // Show loading state
        const button = event.target.closest('button');
        const originalText = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

        // Use the correct URL path that includes /agrinew/
        fetch('/agrinew/api/orders/update-status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                order_id: orderId,
                status: status,
                csrf_token: '<?php echo generate_csrf_token(); ?>'
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Show success message
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-success alert-dismissible fade show';
                alertDiv.innerHTML = `
                    <strong>Success!</strong> ${data.message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                document.querySelector('.container').insertBefore(alertDiv, document.querySelector('.row'));
                
                // Reload the page after a short delay
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                throw new Error(data.message || 'Failed to update order status');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Show error message
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-danger alert-dismissible fade show';
            alertDiv.innerHTML = `
                <strong>Error!</strong> ${error.message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            document.querySelector('.container').insertBefore(alertDiv, document.querySelector('.row'));
            
            // Reset button state
            button.disabled = false;
            button.innerHTML = originalText;
        });
    }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?> 