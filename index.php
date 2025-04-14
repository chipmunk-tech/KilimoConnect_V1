<?php
// Start session
session_start();

// Define the base directory
define('BASE_DIR', __DIR__);

// Include configuration and functions
require_once BASE_DIR . '/includes/config.php';
require_once BASE_DIR . '/includes/functions.php';

// Include header
include BASE_DIR . '/includes/header.php';

// Include the main page content
include BASE_DIR . '/pages/home.php';

// Include footer
include BASE_DIR . '/includes/footer.php';
?> 