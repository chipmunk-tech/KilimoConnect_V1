<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and is a farmer
if (!is_logged_in() || $_SESSION['role'] !== 'farmer') {
    set_flash_message('error', 'Please login as a farmer to view orders.');
    header('Location: ' . SITE_URL . '/pages/login.php');
    exit;
}

$farmer_id = $_SESSION['user_id'];

// Get filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build query based on filters
$where_clause = "o.farmer_id = ?";
$params = [$farmer_id];

if ($status !== 'all') {
    $where_clause .= " AND o.status = ?";
    $params[] = $status;
}

try {
    // First, let's check the table structure
    $table_check = $pdo->query("SHOW TABLES LIKE 'orders'");
    if ($table_check->rowCount() === 0) {
        throw new Exception("Orders table does not exist");
    }

    $columns_check = $pdo->query("SHOW COLUMNS FROM orders");
    $columns = $columns_check->fetchAll(PDO::FETCH_COLUMN);
    error_log("Orders table columns: " . print_r($columns, true));

    // Get total orders count
    $count_sql = "
        SELECT COUNT(DISTINCT o.id) as total 
        FROM orders o
        WHERE {$where_clause}
    ";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_orders = $stmt->fetch()['total'];
    $total_pages = ceil($total_orders / $per_page);

    // Fetch orders with product and buyer details
    $orders_sql = "
        SELECT o.*, 
               p.name as product_name, p.unit,
               oi.quantity, oi.price,
               pi.image_path as product_image,
               u.username as buyer_name, u.phone as buyer_phone,
               u.email as buyer_email
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        LEFT JOIN users u ON o.buyer_id = u.id
        WHERE {$where_clause}
        ORDER BY o.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $pdo->prepare($orders_sql);
    $params[] = $per_page;
    $params[] = $offset;
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Database Error in orders.php: " . $e->getMessage());
    error_log("SQL Query: " . ($orders_sql ?? 'No query executed'));
    error_log("Parameters: " . print_r($params, true));
    die("Error: Unable to fetch orders. Please check the database structure and try again. Details: " . $e->getMessage());
}

$page_title = "Manage Orders - " . SITE_NAME;
require_once __DIR__ . '/../../includes/header-farmer.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Manage Orders</h2>
                </div>
                <div class="card-body">
                    <!-- Filter Form -->
                    <form method="GET" class="mb-4">
                        <div class="row align-items-end">
                            <div class="col-md-3">
                                <label for="status" class="form-label">Filter by Status</label>
                                <select name="status" id="status" class="form-select" onchange="this.form.submit()">
                                    <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Orders</option>
                                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="processing" <?php echo $status === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                    <option value="shipped" <?php echo $status === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                    <option value="delivered" <?php echo $status === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                    <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                        </div>
                    </form>

                    <?php if (empty($orders)): ?>
                        <div class="alert alert-info">No orders found.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Product</th>
                                        <th>Buyer</th>
                                        <th>Quantity</th>
                                        <th>Total Price</th>
                                        <th>Status</th>
                                        <th>Order Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($order['id']); ?></td>
                                            <td>
                                                <?php if ($order['product_image']): ?>
                                                    <img src="<?php echo SITE_URL; ?>/uploads/products/<?php echo htmlspecialchars($order['product_image']); ?>" 
                                                         alt="<?php echo htmlspecialchars($order['product_name']); ?>"
                                                         class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars($order['product_name']); ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($order['buyer_name']); ?><br>
                                                <small><?php echo htmlspecialchars($order['buyer_email']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($order['quantity']); ?> <?php echo htmlspecialchars($order['unit']); ?></td>
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
                                                    <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="view-order.php?id=<?php echo $order['id']; ?>" 
                                                       class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                    <?php if ($order['status'] === 'pending'): ?>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-success"
                                                                onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'processing')">
                                                            <i class="fas fa-check"></i> Accept
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Page navigation" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateOrderStatus(orderId, newStatus) {
    if (confirm('Are you sure you want to update this order\'s status?')) {
        window.location.href = `update-order-status.php?id=${orderId}&status=${newStatus}`;
    }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?> 