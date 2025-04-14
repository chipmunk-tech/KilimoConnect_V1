<?php
$page_title = "Admin Dashboard - " . SITE_NAME;

// Check if user is logged in and is an admin
if (!is_logged_in() || !is_admin()) {
    redirect(SITE_URL);
}

// Get system statistics
$stats = [];

// Total users by role
$stmt = $pdo->query("
    SELECT role, COUNT(*) as count 
    FROM users 
    GROUP BY role
");
$user_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$stats['total_users'] = array_sum($user_stats);
$stats['farmers'] = $user_stats['farmer'] ?? 0;
$stats['buyers'] = $user_stats['buyer'] ?? 0;

// Total products
$stmt = $pdo->query("SELECT COUNT(*) FROM products");
$stats['total_products'] = $stmt->fetchColumn();

// Total orders and revenue
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
        SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as completed_orders,
        SUM(total_amount) as total_revenue
    FROM orders
");
$order_stats = $stmt->fetch(PDO::FETCH_ASSOC);
$stats = array_merge($stats, $order_stats);

// Recent users
$stmt = $pdo->query("
    SELECT * FROM users 
    ORDER BY created_at DESC 
    LIMIT 5
");
$recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent orders
$stmt = $pdo->query("
    SELECT o.*, 
           b.first_name as buyer_name, 
           f.first_name as farmer_name 
    FROM orders o
    JOIN users b ON o.buyer_id = b.id
    JOIN users f ON o.farmer_id = f.id
    ORDER BY o.created_at DESC 
    LIMIT 5
");
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get system logs
$stmt = $pdo->query("
    SELECT l.*, u.username 
    FROM admin_logs l
    JOIN users u ON l.admin_id = u.id
    ORDER BY l.created_at DESC 
    LIMIT 10
");
$recent_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h2>Admin Dashboard</h2>
        <p class="text-muted">Monitor and manage the marketplace system.</p>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3 mb-4">
        <div class="card dashboard-card text-center">
            <div class="card-body">
                <h3 class="card-title"><?php echo $stats['total_users']; ?></h3>
                <p class="card-text">Total Users</p>
                <div class="small text-muted">
                    <?php echo $stats['farmers']; ?> Farmers, <?php echo $stats['buyers']; ?> Buyers
                </div>
                <a href="?page=admin/users" class="btn btn-success btn-sm mt-2">Manage Users</a>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card dashboard-card text-center">
            <div class="card-body">
                <h3 class="card-title"><?php echo $stats['total_products']; ?></h3>
                <p class="card-text">Total Products</p>
                <a href="?page=admin/products" class="btn btn-success btn-sm mt-2">View Products</a>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card dashboard-card text-center">
            <div class="card-body">
                <h3 class="card-title"><?php echo $stats['total_orders']; ?></h3>
                <p class="card-text">Total Orders</p>
                <div class="small text-muted">
                    <?php echo $stats['pending_orders']; ?> Pending
                </div>
                <a href="?page=admin/orders" class="btn btn-success btn-sm mt-2">Manage Orders</a>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card dashboard-card text-center">
            <div class="card-body">
                <h3 class="card-title"><?php echo format_price($stats['total_revenue']); ?></h3>
                <p class="card-text">Total Revenue</p>
                <a href="?page=admin/reports" class="btn btn-success btn-sm mt-2">View Reports</a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Users</h5>
                <a href="?page=admin/users" class="btn btn-light btn-sm">View All</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_users as $user): ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'farmer' ? 'success' : 'primary'); ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="?page=admin/user&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-success">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Orders</h5>
                <a href="?page=admin/orders" class="btn btn-light btn-sm">View All</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Buyer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($order['buyer_name']); ?>
                                        <br>
                                        <small class="text-muted">From: <?php echo htmlspecialchars($order['farmer_name']); ?></small>
                                    </td>
                                    <td><?php echo format_price($order['total_amount']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $order['status'] === 'pending' ? 'warning' : 
                                                ($order['status'] === 'confirmed' ? 'info' : 
                                                ($order['status'] === 'shipped' ? 'primary' : 'success')); 
                                        ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="?page=admin/order&id=<?php echo $order['id']; ?>" class="btn btn-sm btn-success">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Recent System Logs</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Admin</th>
                                <th>Action</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_logs as $log): ?>
                                <tr>
                                    <td><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($log['username']); ?></td>
                                    <td><?php echo htmlspecialchars($log['action']); ?></td>
                                    <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <a href="?page=admin/users/add" class="btn btn-success btn-block w-100 mb-2">Add New User</a>
                    </div>
                    <div class="col-md-3">
                        <a href="?page=admin/products/verify" class="btn btn-success btn-block w-100 mb-2">Verify Products</a>
                    </div>
                    <div class="col-md-3">
                        <a href="?page=admin/reports/generate" class="btn btn-success btn-block w-100 mb-2">Generate Report</a>
                    </div>
                    <div class="col-md-3">
                        <a href="?page=admin/settings" class="btn btn-success btn-block w-100 mb-2">System Settings</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 