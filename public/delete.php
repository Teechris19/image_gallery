<?php
/**
 * Delete Image Handler
 * Processes image deletion (POST only)
 */

define('APP_ROOT', dirname(__DIR__));

require_once APP_ROOT . '/application/core/config.php';
require_once APP_ROOT . '/application/core/database.php';
require_once APP_ROOT . '/application/functions/general.php';
require_once APP_ROOT . '/application/functions/images.php';

// Initialize database tables (first run only)
initializeDatabase();

session_start();

// Only allow POST requests
if (!is_post()) {
    redirect('index.php');
}

// Get image ID
$id = get_input('id');
if (!$id) {
    set_flash('error', 'Image ID is required');
    redirect('index.php');
}

// Delete the image
$result = delete_image($id);

if ($result['success']) {
    set_flash('success', 'Image deleted successfully');
} else {
    set_flash('error', $result['error']);
}

redirect('index.php');
