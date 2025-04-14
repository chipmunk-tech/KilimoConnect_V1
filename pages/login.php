<?php
// Include configuration and functions
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

$page_title = "Login - " . SITE_NAME;
require_once __DIR__ . '/../includes/header.php';

// Check if user is already logged in
if (is_logged_in()) {
    redirect(SITE_URL);
}

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        set_flash_message('danger', 'Invalid security token. Please try again.');
    } else {
        $username = sanitize_input($_POST['username']);
        $password = $_POST['password'];
        
        // Validate credentials
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // Set welcome message
            set_flash_message('success', "Welcome back, " . htmlspecialchars($user['username']) . "!");
            
            // Redirect based on role
            if ($user['role'] === 'admin') {
                redirect(SITE_URL . '/pages/admin/dashboard.php');
            } elseif ($user['role'] === 'farmer') {
                redirect(SITE_URL . '/pages/farmer/dashboard.php');
            } else {
                redirect(SITE_URL . '/pages/buyer/dashboard.php');
            }
        } else {
            set_flash_message('danger', 'Invalid username or password.');
        }
    }
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="text-center">Login</h3>
                </div>
                <div class="card-body">
                    <?php display_flash_message(); ?>
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Login</button>
                        </div>
                    </form>
                    
                    <div class="mt-3 text-center">
                        <p>Don't have an account? <a href="<?php echo SITE_URL; ?>/pages/register.php">Register here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<style>
.features-section {
    background-color: #f8f9fa;
    padding: 20px;
    border-radius: 5px;
    height: 100%;
}

.role-features {
    margin-bottom: 20px;
}

.role-features h5 {
    margin-bottom: 15px;
    font-weight: 600;
}

.role-features ul li {
    margin-bottom: 10px;
    padding-left: 5px;
}

.role-features ul li i {
    font-size: 1.1em;
}

@media (max-width: 768px) {
    .features-section {
        margin-top: 20px;
    }
}
</style> 