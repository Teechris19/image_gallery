<?php
/**
 * Debug Session Page
 * Shows current session and user information
 */

define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/application/core/config.php';
require_once APP_ROOT . '/application/core/database.php';
require_once APP_ROOT . '/application/functions/auth.php';

session_start();

echo "<!DOCTYPE html><html><head><title>Debug Session</title>";
echo "<style>body{font-family:monospace;background:#1e293b;color:#e2e8f0;padding:2rem;max-width:1000px;margin:0 auto;}";
echo ".success{color:#4ade80;} .error{color:#f87171;} .info{color:#60a5fa;}";
echo "pre{background:#0f172a;padding:1rem;border-radius:0.5rem;overflow-x:auto;}";
echo "table{width:100%;border-collapse:collapse;margin-top:1rem;} th,td{border:1px solid #475569;padding:0.5rem;text-align:left;} th{background:#334155;}</style></head><body>";
echo "<h1>🔍 Debug Session</h1>";

echo "<h2>Session Information</h2>";
echo "<pre>Session ID: " . session_id() . "</pre>";
echo "<pre>Session Status: " . session_status() . " (" . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive') . ")</pre>";

echo "<h2>Session Data</h2><pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Authentication Status</h2>";
echo "<table><tr><th>Check</th><th>Result</th></tr>";

$is_logged = is_logged_in();
echo "<tr><td>is_logged_in()</td><td class='" . ($is_logged ? 'success' : 'error') . "'>" . ($is_logged ? '✓ Logged In' : '✗ Not Logged In') . "</td></tr>";

$user = get_logged_in_user();
echo "<tr><td>get_logged_in_user()</td><td>" . ($user ? '✓ Returns user' : '✗ Returns null') . "</td></tr>";

$user_id = get_logged_in_user_id();
echo "<tr><td>get_logged_in_user_id()</td><td>" . ($user_id ? "✓ Returns: $user_id" : '✗ Returns null') . "</td></tr>";

echo "</table>";

if ($user) {
    echo "<h2>Current User</h2><table>";
    foreach ($user as $key => $value) {
        echo "<tr><td><strong>$key</strong></td><td>" . htmlspecialchars(print_r($value, true)) . "</td></tr>";
    }
    echo "</table>";
}

// Test database connection
echo "<h2>Database Test</h2>";
try {
    $pdo = getConnection();
    echo "<p class='success'>✓ Database connection successful</p>";
    
    // Count images
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM images");
    $image_count = $stmt->fetch()['count'];
    echo "<p class='info'>Total images in database: $image_count</p>";
    
    // Get recent images
    if ($image_count > 0) {
        echo "<h3>Recent Images</h3><table><tr><th>ID</th><th>User ID</th><th>Title</th><th>Artist</th><th>Filename</th></tr>";
        $stmt = $pdo->query("SELECT id, user_id, title, artist_name, filename FROM images ORDER BY uploaded_at DESC LIMIT 5");
        while ($row = $stmt->fetch()) {
            echo "<tr><td>{$row['id']}</td><td>{$row['user_id']}</td><td>{$row['title']}</td><td>{$row['artist_name']}</td><td>{$row['filename']}</td></tr>";
        }
        echo "</table>";
    }
    
    // If user is logged in, show their images
    if ($user_id) {
        echo "<h3>Your Images (user_id = $user_id)</h3>";
        $stmt = $pdo->prepare("SELECT id, title, artist_name, filename FROM images WHERE user_id = ? ORDER BY uploaded_at DESC");
        $stmt->execute([$user_id]);
        $user_images = $stmt->fetchAll();
        
        if (count($user_images) > 0) {
            echo "<table><tr><th>ID</th><th>Title</th><th>Artist</th><th>Filename</th></tr>";
            foreach ($user_images as $img) {
                echo "<tr><td>{$img['id']}</td><td>{$img['title']}</td><td>{$img['artist_name']}</td><td>{$img['filename']}</td></tr>";
            }
            echo "</table>";
            echo "<p class='success'>✓ Found " . count($user_images) . " images for your account</p>";
        } else {
            echo "<p class='error'>✗ No images found for your account (user_id = $user_id)</p>";
            echo "<p class='info'>This might mean images were uploaded with a different user_id</p>";
        }
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>✗ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test upload directory
echo "<h2>Upload Directory</h2>";
echo "<table><tr><th>Check</th><th>Result</th></tr>";
echo "<tr><td>UPLOAD_DIR constant</td><td><code>" . UPLOAD_DIR . "</code></td></tr>";
echo "<tr><td>Directory exists</td><td class='" . (is_dir(UPLOAD_DIR) ? 'success' : 'error') . "'>" . (is_dir(UPLOAD_DIR) ? '✓ Exists' : '✗ Missing') . "</td></tr>";
echo "<tr><td>Is writable</td><td class='" . (is_writable(UPLOAD_DIR) ? 'success' : 'error') . "'>" . (is_writable(UPLOAD_DIR) ? '✓ Writable' : '✗ Not writable') . "</td></tr>";

$thumb_dir = THUMB_DIR;
echo "<tr><td>THUMB_DIR constant</td><td><code>$thumb_dir</code></td></tr>";
echo "<tr><td>Directory exists</td><td class='" . (is_dir($thumb_dir) ? 'success' : 'error') . "'>" . (is_dir($thumb_dir) ? '✓ Exists' : '✗ Missing') . "</td></tr>";
echo "<tr><td>Is writable</td><td class='" . (is_writable($thumb_dir) ? 'success' : 'error') . "'>" . (is_writable($thumb_dir) ? '✓ Writable' : '✗ Not writable') . "</td></tr>";
echo "</table>";

echo "<hr>";
echo "<p><a href='index.php' style='color:#a78bfa;'>← Back to Gallery</a> | <a href='profile.php' style='color:#a78bfa;'>My Profile</a> | <a href='fix_db.php' style='color:#a78bfa;'>Fix Database</a></p>";
echo "</body></html>";
?>
