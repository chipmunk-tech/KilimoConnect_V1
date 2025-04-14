<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

// Check if user is logged in and is a buyer
if (!is_logged_in() || $_SESSION['role'] !== 'buyer') {
    set_flash_message('error', 'Please log in as a buyer to view products');
    header('Location: ' . SITE_URL . '/pages/login.php');
    exit;
}

$page_title = "Browse Products - " . SITE_NAME;
require_once __DIR__ . '/../../includes/header-buyer.php';

try {
    // Get all available products with their primary images and farmer info
    $products_sql = "
        SELECT 
            p.id, p.name, p.description, p.price, p.stock_quantity, 
            p.unit, p.category, p.status,
            pi.image_path,
            u.username as farmer_name,
            u.phone as farmer_phone
        FROM products p 
        LEFT JOIN users u ON p.farmer_id = u.id
        LEFT JOIN (
            SELECT product_id, image_path 
            FROM product_images 
            WHERE is_primary = 1
        ) pi ON p.id = pi.product_id
        WHERE p.status = 'available'
        ORDER BY p.created_at DESC";
    
    $stmt = $pdo->prepare($products_sql);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Database Error in products.php: " . $e->getMessage());
    $products = [];
    set_flash_message('error', 'Unable to fetch products. Please try again later.');
}
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Available Products</h1>
        <div>
            <form class="d-flex" method="GET">
                <input type="text" name="search" class="form-control me-2" placeholder="Search products..." 
                       value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button type="submit" class="btn btn-outline-success">Search</button>
            </form>
        </div>
    </div>

    <?php if (empty($products)): ?>
        <div class="alert alert-info">
            No products available at the moment.
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($products as $product): ?>
                <div class="col-md-4 col-lg-3 mb-4">
                    <div class="card h-100">
                        <?php if ($product['image_path']): ?>
                            <img src="<?php echo SITE_URL; ?>/uploads/products/<?php echo htmlspecialchars($product['image_path']); ?>" 
                                 class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 style="height: 200px; object-fit: cover;">
                        <?php else: ?>
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                <i class="fas fa-image fa-3x text-muted"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="card-text">
                                <small class="text-muted">By <?php echo htmlspecialchars($product['farmer_name']); ?></small><br>
                                <strong class="text-primary">TSh <?php echo number_format($product['price'], 2); ?></strong> / <?php echo htmlspecialchars($product['unit']); ?>
                            </p>
                            <p class="card-text">
                                <span class="badge bg-info"><?php echo htmlspecialchars($product['category']); ?></span>
                                <?php if ($product['stock_quantity'] > 0): ?>
                                    <span class="badge bg-success">In Stock (<?php echo $product['stock_quantity']; ?>)</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Out of Stock</span>
                                <?php endif; ?>
                            </p>
                            <div class="d-grid gap-2">
                                <a href="<?php echo SITE_URL; ?>/pages/buyer/view-product.php?id=<?php echo $product['id']; ?>" 
                                   class="btn btn-primary">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                                <?php if ($product['stock_quantity'] > 0): ?>
                                    <button type="button" class="btn btn-outline-danger btn-wishlist" 
                                            data-product-id="<?php echo $product['id']; ?>">
                                        <i class="fas fa-heart"></i> Add to Wishlist
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
document.querySelectorAll('.btn-wishlist').forEach(button => {
    button.addEventListener('click', function() {
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
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?> 