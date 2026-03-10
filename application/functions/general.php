<?php
/**
 * General helper functions
 * Common utilities used throughout the application
 */

// Define APP_ROOT if not already defined
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__, 2));
}

require_once APP_ROOT . '/application/core/config.php';

/**
 * Escape HTML output
 * @param string $string
 * @return string Escaped string
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Alias for e() function
 */
function esc($string) {
    return e($string);
}

/**
 * Redirect to a URL
 * @param string $url Relative or absolute URL
 */
function redirect($url) {
    // Convert relative to absolute if needed
    if (!preg_match('#^https?://#', $url)) {
        $url = BASE_URL . $url;
    }
    header("Location: " . $url);
    exit;
}

/**
 * Check if request is POST
 * @return bool
 */
function is_post() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Get input value from POST or GET
 * @param string $key Input key
 * @param mixed $default Default value if not set
 * @return mixed
 */
function get_input($key, $default = null) {
    if (isset($_POST[$key])) {
        return $_POST[$key];
    }
    if (isset($_GET[$key])) {
        return $_GET[$key];
    }
    return $default;
}

/**
 * Get sanitized input (HTML escaped)
 * @param string $key Input key
 * @param mixed $default Default value
 * @return mixed
 */
function get_sanitized($key, $default = null) {
    $value = get_input($key, $default);
    if (is_string($value)) {
        return e($value);
    }
    return $value;
}

/**
 * Set flash message (requires session)
 * @param string $type Message type (success, error, info, warning)
 * @param string $message Message content
 */
function set_flash($type, $message) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/**
 * Get and clear flash messages
 * @return array Array of flash messages
 */
function get_flash() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/**
 * Generate a random string
 * @param int $length String length
 * @return string
 */
function random_string($length = 10) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Format file size to human readable
 * @param int $bytes Size in bytes
 * @return string Formatted size
 */
function format_size($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

/**
 * Get base URL
 * @return string
 */
function base_url($path = '') {
    return BASE_URL . ltrim($path, '/');
}

/**
 * Display error page and exit
 * @param string $message Error message
 * @param int $code HTTP status code
 */
function show_error($message, $code = 400) {
    http_response_code($code);
    echo "<!DOCTYPE html>
<html>
<head>
    <title>Error - " . e(APP_NAME) . "</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .error { background: #fee; border: 1px solid #c00; padding: 20px; border-radius: 5px; }
        h1 { color: #c00; }
    </style>
</head>
<body>
    <div class='error'>
        <h1>Error</h1>
        <p>" . e($message) . "</p>
        <a href='" . base_url() . "'>Go back to gallery</a>
    </div>
</body>
</html>";
    exit;
}
