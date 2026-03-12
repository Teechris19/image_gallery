<?php
/**
 * Fix All Issues - Images, Profile Upload, Search
 */

define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/application/core/config.php';
require_once APP_ROOT . '/application/core/database.php';
require_once APP_ROOT . '/application/functions/auth.php';
require_once APP_ROOT . '/application/functions/images.php';

session_start();

echo "<!DOCTYPE html><html><head><title>Fix All Issues</title>";
echo "<style>body{font-family:monospace;background:#1e293b;color:#e2e8f0;padding:2rem;max-width:1000px;margin:0 auto;}";
echo ".success{color:#4ade80;} .error{color:#f87171;} .info{color:#60a5fa;}";
echo "table{width:100%;border-collapse:collapse;margin-top:1rem;} th,td{border:1px solid #475569;padding:0.5rem;text-align:left;} th{background:#334155;}";
echo "img{max-width:200px;border-radius:0.5rem;}</style></head><body>";
echo "<h1>🔧 Fix All Issues</h1>";

try {
    $pdo = getConnection();
    
    // Check images
    echo "<h2>1. Image Display Check</h2>";
    $stmt = $pdo->query("SELECT id, user_id, title, filename, artist_name FROM images ORDER BY id DESC");
    $images = $stmt->fetchAll();
    
    if (count($images) > 0) {
        echo "<table><tr><th>ID</th><th>User ID</th><th>Title</th><th>Filename</th><th>File Exists</th><th>Image</th></tr>";
        foreach ($images as $img) {
            $file_exists = file_exists(UPLOAD_DIR . $img['filename']);
            $thumb_exists = file_exists(THUMB_DIR . $img['filename']);
            $status = $file_exists ? '<span class="success">✓</span>' : '<span class="error">✗</span>';
            $thumb_status = $thumb_exists ? '<span class="success">✓</span>' : '<span class="error">✗</span>';
            $img_path = image_url($img['filename']);
            $thumb_path = thumb_url($img['filename']);
            echo "<tr>";
            echo "<td>{$img['id']}</td>";
            echo "<td>{$img['user_id']}</td>";
            echo "<td>{$img['title']}</td>";
            echo "<td>{$img['filename']}<br><small class='info'>Main: $status | Thumb: $thumb_status</small></td>";
            echo "<td>" . ($file_exists ? 'Yes' : 'No') . "</td>";
            echo "<td><a href='$img_path' target='_blank'><img src='$thumb_path' alt='thumb' onerror='this.src=\"data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%22100%22><rect fill=%22%23ddd%22 width=%22100%22 height=%22100%22/><text fill=%22%23999%22 x=%2250%22 y=%2250%22 text-anchor=%22middle%22>No Image</text></svg>\"'></a></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='info'>No images in database</p>";
    }
    
    // Fix: Assign all images to current user if logged in
    if (is_logged_in()) {
        $current_user = get_logged_in_user();
        $current_user_id = get_logged_in_user_id();
        
        echo "<h2>Fix: Assign Images to Current User</h2>";
        echo "<p class='info'>Current user: <strong>{$current_user['username']}</strong> (ID: $current_user_id)</p>";
        
        if (isset($_POST['assign_images'])) {
            $stmt = $pdo->prepare("UPDATE images SET user_id = ?");
            $stmt->execute([$current_user_id]);
            echo "<p class='success'>✓ All images assigned to your account</p>";
        }
        
        echo "<form method='POST'>";
        echo "<button type='submit' name='assign_images' style='padding:0.75rem 1.5rem;background:#7c3aed;color:white;border:none;border-radius:0.5rem;cursor:pointer;'>Assign All Images to My Account (ID: $current_user_id)</button>";
        echo "</form>";
    }
    
    // Check upload directories
    echo "<h2>2. Upload Directories</h2>";
    echo "<table><tr><th>Directory</th><th>Exists</th><th>Writable</th><th>Contents</th></tr>";
    
    $dirs = [
        'UPLOAD_DIR' => UPLOAD_DIR,
        'THUMB_DIR' => THUMB_DIR
    ];
    
    foreach ($dirs as $name => $dir) {
        $exists = is_dir($dir);
        $writable = is_writable($dir);
        $contents = $exists ? count(scandir($dir)) - 2 : 0;
        echo "<tr>";
        echo "<td><code>$name</code><br><small>$dir</small></td>";
        echo "<td class='" . ($exists ? 'success' : 'error') . "'>" . ($exists ? '✓ Yes' : '✗ No') . "</td>";
        echo "<td class='" . ($writable ? 'success' : 'error') . "'>" . ($writable ? '✓ Yes' : '✗ No') . "</td>";
        echo "<td>$contents files</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Create directories if missing
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
        echo "<p class='success'>✓ Created UPLOAD_DIR</p>";
    }
    if (!is_dir(THUMB_DIR)) {
        mkdir(THUMB_DIR, 0755, true);
        echo "<p class='success'>✓ Created THUMB_DIR</p>";
    }
    
    // Check users table for avatar column
    echo "<h2>3. Profile Image Upload Check</h2>";
    $stmt = $pdo->query("SHOW COLUMNS FROM users");
    $has_avatar = false;
    while ($col = $stmt->fetch()) {
        if ($col['Field'] === 'avatar') {
            $has_avatar = true;
            break;
        }
    }
    
    if ($has_avatar) {
        echo "<p class='success'>✓ users table has avatar column</p>";
    } else {
        $pdo->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT NULL AFTER password");
        echo "<p class='success'>✓ Added avatar column to users table</p>";
    }
    
    echo "<hr>";
    echo "<p><a href='index.php' style='color:#a78bfa;'>← Back to Gallery</a> | <a href='debug.php' style='color:#a78bfa;'>Debug</a></p>";
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
?>
