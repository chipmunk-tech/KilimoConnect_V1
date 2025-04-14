<?php
$page_title = "Welcome to " . SITE_NAME;

// Get featured products
$stmt = $pdo->prepare("
    SELECT p.*, u.first_name, u.last_name, pi.image_path 
    FROM products p 
    JOIN users u ON p.farmer_id = u.id 
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 
    WHERE p.status = 'available' 
    ORDER BY p.created_at DESC 
    LIMIT 6
");
$stmt->execute();
$featured_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="jumbotron bg-light p-5 rounded">
            <h1 class="display-4">Welcome to <?php echo SITE_NAME; ?></h1>
            <p class="lead">Connecting farmers directly with buyers for a better agricultural marketplace.</p>
            <hr class="my-4">
            <form id="searchForm" class="form-inline">
                <div class="input-group w-100">
                    <input type="text" class="form-control form-control-lg" id="searchInput" placeholder="Search for crops, vegetables, fruits..." required>
                    <div class="input-group-append">
                        <button class="btn btn-success btn-lg" type="submit">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <h2 class="mb-4">Featured Products</h2>
        <div class="row">
            <?php foreach ($featured_products as $product): ?>
                <div class="col-md-4 mb-4">
                    <div class="card product-card h-100">
                        <?php if ($product['image_path']): ?>
                            <img src="<?php echo SITE_URL . '/' . $product['image_path']; ?>" class="card-img-top product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <?php else: ?>
                            <img src="<?php echo SITE_URL; ?>/assets/images/no-image.jpg" class="card-img-top product-image" alt="No image available">
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="card-text text-muted">
                                By <?php echo htmlspecialchars($product['first_name'] . ' ' . $product['last_name']); ?>
                            </p>
                            <p class="card-text"><?php echo format_price($product['price']); ?> per <?php echo htmlspecialchars($product['unit']); ?></p>
                            <p class="card-text">Stock: <?php echo $product['stock_quantity']; ?> <?php echo htmlspecialchars($product['unit']); ?>s</p>
                            <a href="<?php echo SITE_URL; ?>/product/<?php echo $product['id']; ?>" class="btn btn-success">View Details</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card dashboard-card h-100">
            <div class="card-body text-center">
                <i class="fas fa-seedling fa-3x text-success mb-3"></i>
                <h3>For Farmers</h3>
                <p>Sell your produce directly to buyers and get fair prices for your hard work.</p>
                <a href="<?php echo SITE_URL; ?>/pages/register.php?role=farmer" class="btn btn-success">Register as Farmer</a>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card dashboard-card h-100">
            <div class="card-body text-center">
                <i class="fas fa-shopping-cart fa-3x text-success mb-3"></i>
                <h3>For Buyers</h3>
                <p>Buy fresh produce directly from farmers at competitive prices.</p>
                <a href="<?php echo SITE_URL; ?>/pages/register.php?role=buyer" class="btn btn-success">Register as Buyer</a>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card dashboard-card h-100">
            <div class="card-body text-center">
                <i class="fas fa-truck fa-3x text-success mb-3"></i>
                <h3>Fast Delivery</h3>
                <p>Get your orders delivered quickly and safely to your doorstep.</p>
                <a href="<?php echo SITE_URL; ?>/pages/about.php" class="btn btn-success">Learn More</a>
            </div>
        </div>
    </div>
</div> 