<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

// Check if user is logged in and is a buyer
if (!is_logged_in() || $_SESSION['role'] !== 'buyer') {
    set_flash_message('danger', 'Please login as a buyer to place orders.');
    redirect(SITE_URL . '/pages/login.php');
}

$buyer_id = $_SESSION['user_id'];

// Get product ID from URL
$product_id = isset($_GET['product_id']) ? (int)$_GET['id'] : 0;

// Fetch product details
$stmt = $pdo->prepare("
    SELECT p.*, pi.image_path, u.name as farmer_name, u.phone as farmer_phone
    FROM products p
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    LEFT JOIN users u ON p.farmer_id = u.id
    WHERE p.id = ? AND p.status = 'available'
");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    set_flash_message('danger', 'Product not found or currently unavailable.');
    redirect(SITE_URL . '/pages/buyer/dashboard.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'])) {
    $quantity = filter_var($_POST['quantity'], FILTER_VALIDATE_INT);
    $delivery_address = trim($_POST['delivery_address']);
    $delivery_phone = trim($_POST['delivery_phone']);
    $notes = trim($_POST['notes']);
    
    $errors = [];
    
    // Validate input
    if ($quantity === false || $quantity <= 0) {
        $errors[] = "Please enter a valid quantity.";
    } elseif ($quantity > $product['stock_quantity']) {
        $errors[] = "Requested quantity exceeds available stock.";
    }
    
    if (empty($delivery_address)) {
        $errors[] = "Delivery address is required.";
    }
    
    if (empty($delivery_phone)) {
        $errors[] = "Delivery phone number is required.";
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Calculate total amount
            $total_amount = $quantity * $product['price'];
            
            // Create order
            $stmt = $pdo->prepare("
                INSERT INTO orders (
                    buyer_id, farmer_id, product_id, quantity, total_amount,
                    delivery_address, delivery_phone, notes, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            
            $stmt->execute([
                $buyer_id, $product['farmer_id'], $product_id, $quantity,
                $total_amount, $delivery_address, $delivery_phone, $notes
            ]);
            
            // Update product stock
            $stmt = $pdo->prepare("
                UPDATE products 
                SET stock_quantity = stock_quantity - ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$quantity, $product_id]);
            
            $pdo->commit();
            
            // Send notification to farmer (you can implement this later)
            // send_notification($product['farmer_id'], 'new_order', $order_id);
            
            set_flash_message('success', 'Order placed successfully! The farmer will be notified.');
            redirect(SITE_URL . '/pages/buyer/orders.php');
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Failed to place order: " . $e->getMessage();
        }
    }
}

$page_title = "Place Order - " . SITE_NAME;
require_once __DIR__ . '/../../includes/header-buyer.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h2 class="card-title mb-0">Place Order</h2>
                </div>
                
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="product-image-container mb-3">
                                <?php if ($product['image_path']): ?>
                                    <img src="<?php echo SITE_URL; ?>/uploads/products/<?php echo $product['image_path']; ?>" 
                                         class="product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <?php else: ?>
                                    <div class="product-image-container bg-light d-flex align-items-center justify-content-center">
                                        <i class="fas fa-image fa-3x text-muted"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="col-md-8">
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="text-muted">Sold by: <?php echo htmlspecialchars($product['farmer_name']); ?></p>
                            <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                            <div class="d-flex justify-content-between align-items-center">
                                <h4 class="text-primary mb-0">TSh <?php echo number_format($product['price'], 2); ?> / <?php echo $product['unit']; ?></h4>
                                <span class="badge bg-success">In Stock: <?php echo $product['stock_quantity']; ?> <?php echo $product['unit']; ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <form method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="quantity" class="form-label">Quantity (<?php echo $product['unit']; ?>)</label>
                                <input type="number" class="form-control" id="quantity" name="quantity" 
                                       min="1" max="<?php echo $product['stock_quantity']; ?>" 
                                       value="<?php echo $_POST['quantity'] ?? 1; ?>" required>
                                <div class="form-text">Maximum available: <?php echo $product['stock_quantity']; ?> <?php echo $product['unit']; ?></div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="total" class="form-label">Total Amount</label>
                                <div class="form-control bg-light" id="total">TSh 0.00</div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="delivery_address" class="form-label">Delivery Address</label>
                            <textarea class="form-control" id="delivery_address" name="delivery_address" 
                                    rows="3" required><?php echo $_POST['delivery_address'] ?? ''; ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="delivery_phone" class="form-label">Delivery Phone Number</label>
                            <input type="tel" class="form-control" id="delivery_phone" name="delivery_phone" 
                                   value="<?php echo $_POST['delivery_phone'] ?? ''; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Additional Notes (Optional)</label>
                            <textarea class="form-control" id="notes" name="notes" 
                                    rows="3"><?php echo $_POST['notes'] ?? ''; ?></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="javascript:history.back()" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-shopping-cart me-2"></i>Place Order
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const quantityInput = document.getElementById('quantity');
    const totalDisplay = document.getElementById('total');
    const pricePerUnit = <?php echo $product['price']; ?>;
    
    function updateTotal() {
        const quantity = parseInt(quantityInput.value) || 0;
        const total = quantity * pricePerUnit;
        totalDisplay.textContent = 'TSh ' + total.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }
    
    quantityInput.addEventListener('input', updateTotal);
    updateTotal(); // Initial calculation
    
    // Form validation
    const form = document.querySelector('form');
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?> 