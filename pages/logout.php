<?php
// Include configuration and functions
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Store username for goodbye message
$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';

// Clear all session data
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Set goodbye message
set_flash_message('info', "Goodbye, " . htmlspecialchars($username) . "! You have been successfully logged out.");

// Redirect to login page using SITE_URL
header('Location: ' . SITE_URL . '/pages/login.php');
exit;
?> 