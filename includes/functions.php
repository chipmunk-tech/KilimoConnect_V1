<?php
// Security functions
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Generate a CSRF token and store it in the session
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify the CSRF token from the form submission
 */
function verify_csrf_token() {
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

/**
 * Validate email address
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate phone number (basic validation)
 */
function is_valid_phone($phone) {
    return preg_match('/^[0-9]{10,15}$/', $phone);
}

// User authentication functions
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function is_farmer() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'farmer';
}

function is_buyer() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'buyer';
}

// Redirect function
function redirect($url) {
    header("Location: $url");
    exit();
}

// File upload validation
function validate_image($file) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowed_types)) {
        return "Invalid file type. Only JPG, PNG, and GIF are allowed.";
    }
    
    if ($file['size'] > $max_size) {
        return "File size too large. Maximum size is 5MB.";
    }
    
    return true;
}

// Format price
function format_price($price) {
    return '₹' . number_format($price, 2);
}

// Get user role name
function get_role_name($role) {
    $roles = [
        'admin' => 'Administrator',
        'farmer' => 'Farmer',
        'buyer' => 'Buyer'
    ];
    return $roles[$role] ?? 'Unknown';
}

/**
 * Set a flash message
 */
function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 */
function get_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * Display flash message if exists
 */
function display_flash_message() {
    $message = get_flash_message();
    if ($message) {
        $type = $message['type'];
        $text = $message['message'];
        echo "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
                {$text}
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
              </div>";
    }
}
?> 