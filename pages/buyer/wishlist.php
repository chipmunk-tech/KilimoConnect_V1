<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

// Check if user is logged in and is a buyer
if (!is_logged_in() || $_SESSION['role'] !== 'buyer') {
    set_flash_message('error', 'Please login as a buyer to continue');
    header('Location: ' . SITE_URL . '/pages/login.php');
    exit;
}

$page_title = "My Wishlist - " . SITE_NAME;
require_once __DIR__ . '/../../includes/header-buyer.php';

$buyer_id = $_SESSION['user_id'];

try {
    // Get wishlist items with product details
    $wishlist_sql = "
        SELECT 
            w.id as wishlist_id,
            p.*,
            pi.image_path as product_image,
            u.username as farmer_name,
            u.id as farmer_id
        FROM wishlist w
        JOIN products p ON w.product_id = p.id
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        LEFT JOIN users u ON p.farmer_id = u.id
        WHERE w.buyer_id = ?
        ORDER BY w.created_at DESC
    ";
    $stmt = $pdo->prepare($wishlist_sql);
    $stmt->execute([$buyer_id]);
    $wishlist_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Wishlist Error: " . $e->getMessage());
    set_flash_message('error', 'Unable to fetch wishlist items');
    $wishlist_items = [];
}
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2>My Wishlist (<?php echo count($wishlist_items); ?>)</h2>
        </div>
    </div>

    <?php if (empty($wishlist_items)): ?>
        <div class="alert alert-info">
            Your wishlist is empty. <a href="<?php echo SITE_URL; ?>/pages/buyer/products.php">Browse products</a> to add some!
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($wishlist_items as $item): ?>
                <div class="col-md-4 col-lg-3 mb-4" id="wishlist-item-<?php echo $item['wishlist_id']; ?>">
                    <div class="card h-100 shadow-sm">
                        <?php if ($item['product_image']): ?>
                            <img src="<?php echo SITE_URL; ?>/uploads/products/<?php echo htmlspecialchars($item['product_image']); ?>" 
                                 class="card-img-top" alt="<?php echo htmlspecialchars($item['name']); ?>"
                                 style="height: 200px; object-fit: cover;">
                        <?php else: ?>
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" 
                                 style="height: 200px;">
                                <i class="fas fa-image fa-3x text-muted"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($item['name']); ?></h5>
                            <p class="card-text">
                                <small class="text-muted">
                                    By <a href="../farmer-profile.php?id=<?php echo $item['farmer_id']; ?>">
                                        <?php echo htmlspecialchars($item['farmer_name']); ?>
                                    </a>
                                </small>
                            </p>
                            <p class="card-text">
                                <span class="badge bg-primary"><?php echo htmlspecialchars($item['category']); ?></span>
                                <span class="badge bg-<?php echo $item['stock_quantity'] > 0 ? 'success' : 'danger'; ?>">
                                    <?php echo $item['stock_quantity']; ?> in stock
                                </span>
                            </p>
                            <h4 class="text-primary mb-3">TSh <?php echo number_format($item['price'], 2); ?></h4>
                            
                            <div class="d-grid gap-2">
                                <?php if ($item['stock_quantity'] > 0 && $item['status'] === 'available'): ?>
                                    <a href="<?php echo SITE_URL; ?>/pages/buyer/view-product.php?id=<?php echo $item['id']; ?>" class="btn btn-primary">
                                        View Details
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-secondary" disabled>Out of Stock</button>
                                <?php endif; ?>
                                <button type="button" class="btn btn-outline-danger" 
                                        onclick="removeFromWishlist(<?php echo $item['id']; ?>, <?php echo $item['wishlist_id']; ?>)">
                                    Remove from Wishlist
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function removeFromWishlist(productId, wishlistId) {
    if (confirm('Are you sure you want to remove this item from your wishlist?')) {
        fetch('<?php echo SITE_URL; ?>/api/wishlist/toggle.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                product_id: productId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove the item from the page
                document.getElementById('wishlist-item-' + wishlistId).remove();
                
                // Update wishlist count in header if it exists
                const wishlistCount = document.querySelector('.wishlist-count');
                if (wishlistCount) {
                    const currentCount = parseInt(wishlistCount.textContent) - 1;
                    wishlistCount.textContent = currentCount;
                }
                
                // Show empty message if no items left
                const remainingItems = document.querySelectorAll('[id^="wishlist-item-"]');
                if (remainingItems.length === 0) {
                    location.reload(); // Reload to show empty message
                }
            } else {
                alert('Failed to remove item from wishlist');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while removing the item');
        });
    }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?> 