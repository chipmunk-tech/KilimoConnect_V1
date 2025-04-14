<?php
// Include configuration and functions
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

$page_title = "Buyer Dashboard - " . SITE_NAME;
require_once __DIR__ . '/../../includes/header-buyer.php';

// Check if user is logged in and is a buyer
if (!is_logged_in() || $_SESSION['role'] !== 'buyer') {
    set_flash_message('error', 'Please login as a buyer to continue');
    header('Location: ' . SITE_URL . '/pages/login.php');
    exit;
}

$buyer_id = $_SESSION['user_id'];

// Initialize variables with default values
$stats = [
    'total_orders' => 0,
    'pending_orders' => 0,
    'delivered_orders' => 0,
    'total_spent' => 0
];
$recent_orders = [];
$recommended_products = [];

try {
    // Get buyer's statistics
    $stats_sql = "
        SELECT 
            COUNT(DISTINCT o.id) as total_orders,
            COUNT(DISTINCT CASE WHEN o.status = 'pending' THEN o.id END) as pending_orders,
            COUNT(DISTINCT CASE WHEN o.status = 'delivered' THEN o.id END) as delivered_orders,
            COALESCE(SUM(o.total_amount), 0) as total_spent
        FROM orders o
        WHERE o.buyer_id = ?
    ";
    $stmt = $pdo->prepare($stats_sql);
    $stmt->execute([$buyer_id]);
    $db_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($db_stats) {
        $stats = $db_stats;
    }

    // Get recent orders with their items
    $orders_sql = "
        SELECT DISTINCT
            o.id,
            o.total_amount,
            o.status,
            o.created_at,
            oi.quantity,
            oi.price,
            p.name as product_name,
            pi.image_path as product_image,
            u.username as farmer_name
        FROM orders o
        LEFT JOIN order_items oi ON oi.order_id = o.id
        LEFT JOIN products p ON oi.product_id = p.id
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        LEFT JOIN users u ON o.farmer_id = u.id
        WHERE o.buyer_id = ?
        ORDER BY o.created_at DESC
        LIMIT 5
    ";
    $stmt = $pdo->prepare($orders_sql);
    $stmt->execute([$buyer_id]);
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get recommended products
    $recommended_sql = "
        SELECT DISTINCT
            p.id,
            p.name,
            p.price,
            p.stock_quantity,
            p.status,
            pi.image_path as product_image,
            u.username as farmer_name
        FROM products p
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        LEFT JOIN users u ON p.farmer_id = u.id
        WHERE p.status = 'available'
        AND p.stock_quantity > 0
        ORDER BY RAND()
        LIMIT 6
    ";
    $stmt = $pdo->prepare($recommended_sql);
    $stmt->execute();
    $recommended_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Database Error in dashboard.php: " . $e->getMessage());
    set_flash_message('error', 'Unable to fetch dashboard data. Please try again later.');
    // Continue with default empty values instead of dying
}
?>

<div class="container-fluid py-4">
    <!-- Welcome Message -->
    <div class="row mb-4">
        <div class="col-12">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Orders</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_orders']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Orders</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['pending_orders']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Delivered Orders</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['delivered_orders']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Spent</div>
                            <h2>TSh <?php echo number_format($stats['total_spent'], 2); ?></h2>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-money-bill fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Orders</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_orders)): ?>
                        <p class="text-muted">No orders yet. Start shopping!</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Product</th>
                                        <th>Farmer</th>
                                        <th>Quantity</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_orders as $order): ?>
                                        <tr>
                                            <td>#<?php echo $order['id']; ?></td>
                                            <td>
                                                <?php if ($order['product_image']): ?>
                                                    <img src="<?php echo SITE_URL; ?>/uploads/products/<?php echo htmlspecialchars($order['product_image']); ?>" 
                                                         alt="<?php echo htmlspecialchars($order['product_name']); ?>"
                                                         class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars($order['product_name']); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($order['farmer_name']); ?></td>
                                            <td><?php echo htmlspecialchars($order['quantity']); ?></td>
                                            <td>TSh <?php echo number_format($order['quantity'] * $order['price'], 2); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo match($order['status']) {
                                                        'pending' => 'warning',
                                                        'processing' => 'info',
                                                        'shipped' => 'primary',
                                                        'delivered' => 'success',
                                                        'cancelled' => 'danger',
                                                        default => 'secondary'
                                                    };
                                                ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                            <td>
                                                <a href="view-order.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-end mt-3">
                            <a href="orders.php" class="btn btn-primary">View All Orders</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Recommended Products -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recommended Products</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recommended_products)): ?>
                        <p class="text-muted">No products available at the moment.</p>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($recommended_products as $product): ?>
                                <div class="col-md-4 col-lg-2 mb-4">
                                    <div class="card h-100">
                                        <?php if ($product['product_image']): ?>
                                            <img src="<?php echo SITE_URL; ?>/uploads/products/<?php echo htmlspecialchars($product['product_image']); ?>" 
                                                 class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>"
                                                 style="height: 150px; object-fit: cover;">
                                        <?php endif; ?>
                                        <div class="card-body">
                                            <h6 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h6>
                                            <p class="card-text">
                                                <small class="text-muted">By <?php echo htmlspecialchars($product['farmer_name']); ?></small><br>
                                                <strong>TSh <?php echo number_format($product['price'], 2); ?></strong>
                                            </p>
                                            <a href="<?php echo SITE_URL; ?>/pages/buyer/view-product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-primary">View Details</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="text-end mt-3">
                            <a href="<?php echo SITE_URL; ?>/pages/buyer/products.php" class="btn btn-primary">Browse All Products</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?> 