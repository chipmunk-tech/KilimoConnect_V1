<?php
// Include configuration and functions
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

$page_title = "Farmer Dashboard - " . SITE_NAME;
require_once __DIR__ . '/../../includes/header-farmer.php';

// Check if user is logged in and is a farmer
if (!is_logged_in() || $_SESSION['role'] !== 'farmer') {
    set_flash_message('error', 'Please login as a farmer to access dashboard');
    header('Location: ' . SITE_URL . '/pages/login.php');
    exit;
}

$farmer_id = $_SESSION['user_id'];

try {
    // Get farmer's statistics
    $stats_sql = "
        SELECT 
            COUNT(DISTINCT o.id) as total_orders,
            COUNT(DISTINCT CASE WHEN o.status = 'pending' THEN o.id END) as pending_orders,
            COUNT(DISTINCT CASE WHEN o.status = 'delivered' THEN o.id END) as completed_orders,
            SUM(CASE WHEN o.status = 'delivered' THEN o.total_amount ELSE 0 END) as total_earnings,
            COUNT(DISTINCT p.id) as total_products
        FROM users u
        LEFT JOIN orders o ON u.id = o.farmer_id
        LEFT JOIN products p ON u.id = p.farmer_id
        WHERE u.id = ?
        GROUP BY u.id";

    $stmt = $pdo->prepare($stats_sql);
    $stmt->execute([$farmer_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get recent orders
    $orders_sql = "
        SELECT 
            o.id as order_id,
            o.total_amount,
            o.status,
            o.created_at,
            u.username as buyer_name,
            p.name as product_name
        FROM orders o
        JOIN users u ON o.buyer_id = u.id
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        WHERE o.farmer_id = ?
        ORDER BY o.created_at DESC
        LIMIT 5";

    $stmt = $pdo->prepare($orders_sql);
    $stmt->execute([$farmer_id]);
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get low stock products
    $products_sql = "
        SELECT id, name, stock_quantity, unit
        FROM products
        WHERE farmer_id = ? AND stock_quantity <= 10
        ORDER BY stock_quantity ASC
        LIMIT 5";

    $stmt = $pdo->prepare($products_sql);
    $stmt->execute([$farmer_id]);
    $low_stock_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Database Error in farmer dashboard: " . $e->getMessage());
    set_flash_message('error', 'Unable to load dashboard data. Please try again later.');
    $stats = [
        'total_orders' => 0,
        'pending_orders' => 0,
        'completed_orders' => 0,
        'total_earnings' => 0,
        'total_products' => 0
    ];
    $recent_orders = [];
    $low_stock_products = [];
}
?>

<div class="container py-4">
    <h1 class="h3 mb-4">Farmer Dashboard</h1>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5 class="card-title">Total Orders</h5>
                    <h2 class="card-text"><?php echo number_format($stats['total_orders']); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <h5 class="card-title">Pending Orders</h5>
                    <h2 class="card-text"><?php echo number_format($stats['pending_orders']); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5 class="card-title">Completed Orders</h5>
                    <h2 class="card-text"><?php echo number_format($stats['completed_orders']); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <h5 class="card-title">Total Earnings</h5>
                    <h2 class="card-text">TSh <?php echo number_format($stats['total_earnings'], 2); ?></h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Orders -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Recent Orders</h5>
                    <a href="orders.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Product</th>
                                    <th>Buyer</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): ?>
                                    <tr>
                                        <td>#<?php echo $order['order_id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                        <td><?php echo htmlspecialchars($order['buyer_name']); ?></td>
                                        <td>TSh <?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td>
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
                                        </td>
                                        <td>
                                            <a href="view-order.php?id=<?php echo $order['order_id']; ?>" 
                                               class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($recent_orders)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No orders found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Low Stock Products -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Low Stock Products</h5>
                    <a href="products.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <?php foreach ($low_stock_products as $product): ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($product['name']); ?></h6>
                                    <small class="text-danger">
                                        <?php echo $product['stock_quantity'] . ' ' . htmlspecialchars($product['unit']); ?> left
                                    </small>
                                </div>
                                <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-warning mt-2">
                                    <i class="fas fa-plus"></i> Add Stock
                                </a>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($low_stock_products)): ?>
                            <div class="list-group-item text-center text-muted">
                                No low stock products
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?> 