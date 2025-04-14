<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

// Check if user is logged in and is a buyer
if (!is_logged_in() || $_SESSION['role'] !== 'buyer') {
    set_flash_message('error', 'Please log in as a buyer to view products');
    header('Location: ' . SITE_URL . '/pages/login.php');
    exit;
}

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$product_id) {
    set_flash_message('error', 'Invalid product ID');
    header('Location: ' . SITE_URL . '/pages/buyer/products.php');
    exit;
}

try {
    // Get product details with farmer info
    $product_sql = "
        SELECT 
            p.*, 
            u.username as farmer_name,
            u.phone as farmer_phone,
            u.email as farmer_email
        FROM products p 
        LEFT JOIN users u ON p.farmer_id = u.id
        WHERE p.id = ? AND p.status = 'available'";
    
    $stmt = $pdo->prepare($product_sql);
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        set_flash_message('error', 'Product not found');
        header('Location: ' . SITE_URL . '/pages/buyer/products.php');
        exit;
    }

    // Get all images for this product
    $images_sql = "SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC";
    $stmt = $pdo->prepare($images_sql);
    $stmt->execute([$product_id]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Check if product is in user's wishlist
    $wishlist_sql = "SELECT 1 FROM wishlist WHERE buyer_id = ? AND product_id = ?";
    $stmt = $pdo->prepare($wishlist_sql);
    $stmt->execute([$_SESSION['user_id'], $product_id]);
    $in_wishlist = (bool)$stmt->fetch();

    $page_title = $product['name'] . " - " . SITE_NAME;
    require_once __DIR__ . '/../../includes/header-buyer.php';

} catch (PDOException $e) {
    error_log("Database Error in view-product.php: " . $e->getMessage());
    set_flash_message('error', 'Unable to fetch product details. Please try again later.');
    header('Location: ' . SITE_URL . '/pages/buyer/products.php');
    exit;
}

// Handle order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        set_flash_message('error', 'Invalid request');
        header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $product_id);
        exit;
    }

    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
    $delivery_address = isset($_POST['delivery_address']) ? trim($_POST['delivery_address']) : '';

    if ($quantity <= 0) {
        set_flash_message('error', 'Please enter a valid quantity');
    } elseif ($quantity > $product['stock_quantity']) {
        set_flash_message('error', 'Requested quantity exceeds available stock');
    } elseif (empty($delivery_address)) {
        set_flash_message('error', 'Please enter a delivery address');
    } else {
        try {
            $pdo->beginTransaction();

            // Create order
            $order_sql = "INSERT INTO orders (buyer_id, farmer_id, total_amount, status, delivery_address) 
                         VALUES (?, ?, ?, 'pending', ?)";
            $total_amount = $quantity * $product['price'];
            
            $stmt = $pdo->prepare($order_sql);
            $stmt->execute([$_SESSION['user_id'], $product['farmer_id'], $total_amount, $delivery_address]);
            $order_id = $pdo->lastInsertId();

            // Create order item
            $item_sql = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($item_sql);
            $stmt->execute([$order_id, $product_id, $quantity, $product['price']]);

            // Update product stock
            $update_stock_sql = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?";
            $stmt = $pdo->prepare($update_stock_sql);
            $stmt->execute([$quantity, $product_id]);

            $pdo->commit();
            set_flash_message('success', 'Order placed successfully');
            header("Location: " . SITE_URL . "/pages/buyer/orders.php");
            exit;

        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Order Error: " . $e->getMessage());
            set_flash_message('error', 'Failed to place order. Please try again.');
        }
    }
}
?>

<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/pages/buyer/products.php">Products</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($product['name']); ?></li>
        </ol>
    </nav>

    <div class="row">
        <!-- Product Images -->
        <div class="col-md-6 mb-4">
            <?php if (!empty($images)): ?>
                <div id="productCarousel" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <?php foreach ($images as $index => $image): ?>
                            <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                <img src="<?php echo SITE_URL; ?>/uploads/products/<?php echo htmlspecialchars($image['image_path']); ?>" 
                                     class="d-block w-100" alt="<?php echo htmlspecialchars($product['name']); ?>"
                                     style="height: 400px; object-fit: cover;">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($images) > 1): ?>
                        <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Previous</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Next</span>
                        </button>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="bg-light d-flex align-items-center justify-content-center" style="height: 400px;">
                    <i class="fas fa-image fa-3x text-muted"></i>
                </div>
            <?php endif; ?>
        </div>

        <!-- Product Details -->
        <div class="col-md-6">
            <h1 class="mb-3"><?php echo htmlspecialchars($product['name']); ?></h1>
            
            <div class="mb-3">
                <span class="badge bg-info"><?php echo htmlspecialchars($product['category']); ?></span>
                <?php if ($product['stock_quantity'] > 0): ?>
                    <span class="badge bg-success">In Stock (<?php echo $product['stock_quantity']; ?> <?php echo htmlspecialchars($product['unit']); ?>)</span>
                <?php else: ?>
                    <span class="badge bg-danger">Out of Stock</span>
                <?php endif; ?>
            </div>

            <h3 class="text-primary mb-3">
                TSh <?php echo number_format($product['price'], 2); ?> / <?php echo htmlspecialchars($product['unit']); ?>
            </h3>

            <div class="mb-4">
                <h5>Description</h5>
                <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
            </div>

            <div class="mb-4">
                <h5>Farmer Information</h5>
                <p class="mb-1">
                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($product['farmer_name']); ?>
                </p>
                <p class="mb-1">
                    <i class="fas fa-phone"></i> <?php echo htmlspecialchars($product['farmer_phone']); ?>
                </p>
                <p>
                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($product['farmer_email']); ?>
                </p>
            </div>

            <div class="mb-4">
                <button type="button" class="btn <?php echo $in_wishlist ? 'btn-danger' : 'btn-outline-danger'; ?> btn-wishlist"
                        data-product-id="<?php echo $product_id; ?>">
                    <i class="fas fa-heart"></i> 
                    <?php echo $in_wishlist ? 'Remove from Wishlist' : 'Add to Wishlist'; ?>
                </button>
            </div>

            <?php if ($product['stock_quantity'] > 0): ?>
                <form method="POST" class="mb-4">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Quantity (<?php echo htmlspecialchars($product['unit']); ?>)</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" 
                               min="1" max="<?php echo $product['stock_quantity']; ?>" required>
                        <div class="form-text">Available: <?php echo $product['stock_quantity']; ?> <?php echo htmlspecialchars($product['unit']); ?></div>
                    </div>

                    <div class="mb-3">
                        <label for="delivery_address" class="form-label">Delivery Address</label>
                        <textarea class="form-control" id="delivery_address" name="delivery_address" rows="3" required></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg">Place Order</button>
                </form>
            <?php else: ?>
                <div class="alert alert-warning">
                    This product is currently out of stock.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.querySelector('.btn-wishlist')?.addEventListener('click', function() {
    const productId = this.dataset.productId;
    fetch('<?php echo SITE_URL; ?>/api/wishlist/toggle.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId,
            csrf_token: '<?php echo generate_csrf_token(); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            this.innerHTML = data.in_wishlist ? 
                '<i class="fas fa-heart"></i> Remove from Wishlist' : 
                '<i class="fas fa-heart"></i> Add to Wishlist';
            this.classList.toggle('btn-danger');
            this.classList.toggle('btn-outline-danger');
        } else {
            alert(data.message || 'Failed to update wishlist');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to update wishlist');
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?> 