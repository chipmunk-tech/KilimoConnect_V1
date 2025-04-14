<?php
// Include configuration and functions
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

$page_title = "My Products - " . SITE_NAME;
require_once __DIR__ . '/../../includes/header-farmer.php';

// Check if user is logged in and is a farmer
if (!is_logged_in() || $_SESSION['role'] !== 'farmer') {
    set_flash_message('danger', 'Please login as a farmer to access this page.');
    redirect(SITE_URL . '/pages/login.php');
}

$farmer_id = $_SESSION['user_id'];

// Handle product deletion
if (isset($_POST['delete_product']) && isset($_POST['product_id'])) {
    $product_id = (int)$_POST['product_id'];
    
    // Verify that the product belongs to the farmer
    $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND farmer_id = ?");
    $stmt->execute([$product_id, $farmer_id]);
    
    if ($stmt->fetch()) {
        // Delete product images first
        $stmt = $pdo->prepare("DELETE FROM product_images WHERE product_id = ?");
        $stmt->execute([$product_id]);
        
        // Delete the product
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        if ($stmt->execute([$product_id])) {
            set_flash_message('success', 'Product deleted successfully.');
        } else {
            set_flash_message('danger', 'Failed to delete product.');
        }
    } else {
        set_flash_message('danger', 'Product not found or you do not have permission to delete it.');
    }
    
    redirect(SITE_URL . '/pages/farmer/products.php');
}

// Get all products for the farmer
$stmt = $pdo->prepare("
    SELECT p.*, 
           (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
    FROM products p
    WHERE p.farmer_id = ?
    ORDER BY p.created_at DESC
");
$stmt->execute([$farmer_id]);
$products = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>My Products</h2>
        <a href="<?php echo SITE_URL; ?>/pages/farmer/add-product.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Add New Product
        </a>
    </div>

    <?php display_flash_message(); ?>

    <div class="row">
        <?php if ($products): ?>
            <?php foreach ($products as $product): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <?php if ($product['primary_image']): ?>
                            <div class="product-image-container">
                                <img src="<?php echo SITE_URL; ?>/uploads/products/<?php echo $product['primary_image']; ?>" 
                                     class="card-img-top product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            </div>
                        <?php else: ?>
                            <div class="product-image-container bg-light d-flex align-items-center justify-content-center">
                                <i class="fas fa-image fa-3x text-muted"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="card-text">
                                <strong>Category:</strong> <?php echo htmlspecialchars($product['category']); ?><br>
                                <strong>Price:</strong> TSh <?php echo number_format($product['price'], 2); ?><br>
                                <strong>Stock:</strong> <?php echo $product['stock_quantity']; ?> <?php echo $product['unit']; ?><br>
                                <strong>Status:</strong> 
                                <span class="badge bg-<?php echo $product['status'] === 'available' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($product['status']); ?>
                                </span>
                            </p>
                        </div>
                        
                        <div class="card-footer bg-white">
                            <div class="d-flex justify-content-between">
                                <a href="<?php echo SITE_URL; ?>/pages/farmer/edit-product.php?id=<?php echo $product['id']; ?>" 
                                   class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit me-1"></i> Edit
                                </a>
                                <button type="button" class="btn btn-sm btn-danger" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#deleteModal<?php echo $product['id']; ?>">
                                    <i class="fas fa-trash me-1"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Delete Confirmation Modal -->
                <div class="modal fade" id="deleteModal<?php echo $product['id']; ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Confirm Delete</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you want to delete "<?php echo htmlspecialchars($product['name']); ?>"?</p>
                                <p class="text-danger">This action cannot be undone.</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <button type="submit" name="delete_product" class="btn btn-danger">
                                        Delete Product
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    You haven't added any products yet. 
                    <a href="<?php echo SITE_URL; ?>/pages/farmer/add-product.php" class="alert-link">Add your first product</a>.
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?> 