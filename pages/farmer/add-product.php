<?php
// Include configuration and functions
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

$page_title = "Add New Product - " . SITE_NAME;
require_once __DIR__ . '/../../includes/header-farmer.php';

// Check if user is logged in and is a farmer
if (!is_logged_in() || $_SESSION['role'] !== 'farmer') {
    set_flash_message('error', 'Please login as a farmer to continue');
    header('Location: ' . SITE_URL . '/pages/login.php');
    exit;
}

$farmer_id = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'])) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price = filter_var($_POST['price'] ?? 0, FILTER_VALIDATE_FLOAT);
    $stock_quantity = filter_var($_POST['stock_quantity'] ?? 0, FILTER_VALIDATE_INT);
    $unit = trim($_POST['unit'] ?? '');
    $status = trim($_POST['status'] ?? 'available');
    $errors = [];

    // Validate input
    if (empty($name)) {
        $errors[] = "Product name is required.";
    }
    if (empty($description)) {
        $errors[] = "Product description is required.";
    }
    if (empty($category)) {
        $errors[] = "Product category is required.";
    }
    if ($price === false || $price <= 0) {
        $errors[] = "Valid price is required.";
    }
    if ($stock_quantity === false || $stock_quantity < 0) {
        $errors[] = "Valid stock quantity is required.";
    }
    if (empty($unit)) {
        $errors[] = "Unit of measurement is required.";
    }

    // Handle image uploads
    $uploaded_images = [];
    if (!empty($_FILES['images']['name'][0])) {
        $total_images = count($_FILES['images']['name']);
        
        for ($i = 0; $i < $total_images; $i++) {
            if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $_FILES['images']['name'][$i],
                    'type' => $_FILES['images']['type'][$i],
                    'tmp_name' => $_FILES['images']['tmp_name'][$i],
                    'error' => $_FILES['images']['error'][$i],
                    'size' => $_FILES['images']['size'][$i]
                ];
                
                // Validate image
                if (!in_array($file['type'], ALLOWED_IMAGE_TYPES)) {
                    $errors[] = "Invalid image type for {$file['name']}. Allowed types: " . implode(', ', ALLOWED_IMAGE_TYPES);
                    continue;
                }
                
                if ($file['size'] > MAX_FILE_SIZE) {
                    $errors[] = "Image {$file['name']} is too large. Maximum size: " . (MAX_FILE_SIZE / 1024 / 1024) . "MB";
                    continue;
                }
                
                // Generate unique filename
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = uniqid() . '.' . $extension;
                
                // Ensure upload directory exists
                $upload_dir = __DIR__ . '/../../uploads/products';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $upload_path = $upload_dir . '/' . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    $uploaded_images[] = $filename;
                } else {
                    $errors[] = "Failed to upload {$file['name']}. Please check directory permissions.";
                }
            }
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Insert product
            $stmt = $pdo->prepare("
                INSERT INTO products (
                    farmer_id, name, description, category, price, 
                    stock_quantity, unit, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $_SESSION['user_id'], $name, $description, $category, $price,
                $stock_quantity, $unit, $status
            ]);
            
            $product_id = $pdo->lastInsertId();
            
            // Insert images
            if (!empty($uploaded_images)) {
                $stmt = $pdo->prepare("
                    INSERT INTO product_images (product_id, image_path, is_primary, created_at)
                    VALUES (?, ?, ?, NOW())
                ");
                
                // Set first image as primary
                $stmt->execute([$product_id, $uploaded_images[0], 1]);
                
                // Insert remaining images
                for ($i = 1; $i < count($uploaded_images); $i++) {
                    $stmt->execute([$product_id, $uploaded_images[$i], 0]);
                }
            }
            
            $pdo->commit();
            set_flash_message('success', 'Product added successfully.');
            header('Location: ' . SITE_URL . '/pages/farmer/products.php');
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Failed to add product: " . $e->getMessage();
        }
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Add New Product</h2>
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

                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Product Name</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4" required><?php 
                                echo htmlspecialchars($_POST['description'] ?? ''); 
                            ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="">Select Category</option>
                                    <option value="vegetables" <?php echo ($_POST['category'] ?? '') === 'vegetables' ? 'selected' : ''; ?>>Vegetables</option>
                                    <option value="fruits" <?php echo ($_POST['category'] ?? '') === 'fruits' ? 'selected' : ''; ?>>Fruits</option>
                                    <option value="grains" <?php echo ($_POST['category'] ?? '') === 'grains' ? 'selected' : ''; ?>>Grains</option>
                                    <option value="dairy" <?php echo ($_POST['category'] ?? '') === 'dairy' ? 'selected' : ''; ?>>Dairy</option>
                                    <option value="meat" <?php echo ($_POST['category'] ?? '') === 'meat' ? 'selected' : ''; ?>>Meat</option>
                                    <option value="poultry" <?php echo ($_POST['category'] ?? '') === 'poultry' ? 'selected' : ''; ?>>Poultry</option>
                                    <option value="other" <?php echo ($_POST['category'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="price" class="form-label">Price (TSh)</label>
                                <input type="number" class="form-control" id="price" name="price" 
                                       value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>" 
                                       step="0.01" min="0" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="stock_quantity" class="form-label">Stock Quantity</label>
                                <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" 
                                       value="<?php echo htmlspecialchars($_POST['stock_quantity'] ?? ''); ?>" 
                                       min="0" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="unit" class="form-label">Unit of Measurement</label>
                                <select class="form-select" id="unit" name="unit" required>
                                    <option value="">Select Unit</option>
                                    <option value="kg" <?php echo ($_POST['unit'] ?? '') === 'kg' ? 'selected' : ''; ?>>Kilogram (kg)</option>
                                    <option value="g" <?php echo ($_POST['unit'] ?? '') === 'g' ? 'selected' : ''; ?>>Gram (g)</option>
                                    <option value="l" <?php echo ($_POST['unit'] ?? '') === 'l' ? 'selected' : ''; ?>>Liter (l)</option>
                                    <option value="ml" <?php echo ($_POST['unit'] ?? '') === 'ml' ? 'selected' : ''; ?>>Milliliter (ml)</option>
                                    <option value="piece" <?php echo ($_POST['unit'] ?? '') === 'piece' ? 'selected' : ''; ?>>Piece</option>
                                    <option value="dozen" <?php echo ($_POST['unit'] ?? '') === 'dozen' ? 'selected' : ''; ?>>Dozen</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="available" <?php echo ($_POST['status'] ?? '') === 'available' ? 'selected' : ''; ?>>Available</option>
                                <option value="out_of_stock" <?php echo ($_POST['status'] ?? '') === 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="images" class="form-label">Product Images</label>
                            <input type="file" class="form-control" id="images" name="images[]" 
                                   accept="image/*" multiple>
                            <div class="form-text">
                                Upload multiple images (max 5). First image will be used as primary image.
                                Maximum file size: <?php echo (MAX_FILE_SIZE / 1024 / 1024); ?>MB
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="<?php echo SITE_URL; ?>/pages/farmer/products.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Products
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Product
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?> 