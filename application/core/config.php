<?php
/**
 * Configuration file for Image Gallery
 * Defines constants, paths, and settings
 */

// Define APP_ROOT if not already defined
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__, 2));
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'image_gallary_display');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application paths
define('UPLOAD_DIR', APP_ROOT . '/public/uploads/');
define('THUMB_DIR', APP_ROOT . '/public/uploads/thumbs/');
define('BASE_URL', '/public/');

// Upload settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Thumbnail settings
define('THUMB_WIDTH', 300);
define('THUMB_HEIGHT', 300);

// Application settings
define('APP_NAME', 'Image Gallery');
define('TIMEZONE', 'UTC');

// Set timezone
date_default_timezone_set(TIMEZONE);

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
