<?php
/**
 * Fix User Images
 * Updates images to be associated with current logged-in user
 */

define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/application/core/config.php';
require_once APP_ROOT . '/application/core/database.php';
require_once APP_ROOT . '/application/functions/auth.php';

session_start();

echo "<!DOCTYPE html><html><head><title>Fix User Images</title>";
echo "<style>body{font-family:monospace;background:#1e293b;color:#e2e8f0;padding:2rem;max-width:800px;margin:0 auto;}";
echo ".success{color:#4ade80;} .error{color:#f87171;} .info{color:#60a5fa;}";
echo "table{width:100%;border-collapse:collapse;margin-top:1rem;} th,td{border:1px solid #475569;padding:0.5rem;text-align:left;} th{background:#334155;}</style></head><body>";
echo "<h1>🔧 Fix User Images</h1>";

if (!is_logged_in()) {
    echo "<p class='error'>✗ Please login first</p>";
    echo "<p><a href='index.php' style='color:#a78bfa;'>← Go to Login</a></p>";
    exit;
}

$user = get_logged_in_user();
$user_id = get_logged_in_user_id();

echo "<p class='info'>Current user: <strong>{$user['username']}</strong> (user_id = $user_id)</p>";

try {
    $pdo = getConnection();
    
    // Get all images that don't have the correct user_id
    echo "<h2>Current Images in Database</h2>";
    $stmt = $pdo->query("SELECT id, user_id, title, artist_name, filename FROM images ORDER BY id DESC");
    $all_images = $stmt->fetchAll();
    
    if (count($all_images) > 0) {
        echo "<table><tr><th>ID</th><th>Current user_id</th><th>Title</th><th>Artist</th><th>Status</th></tr>";
        foreach ($all_images as $img) {
            $status = $img['user_id'] == $user_id ? '<span class="success">✓ Correct</span>' : '<span class="error">✗ Wrong user</span>';
            echo "<tr><td>{$img['id']}</td><td>{$img['user_id']}</td><td>{$img['title']}</td><td>{$img['artist_name']}</td><td>$status</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='info'>No images in database</p>";
    }
    
    // Fix option
    echo "<h2>Fix Options</h2>";
    
    // Option 1: Update all images to current user
    if (isset($_POST['fix_all'])) {
        $stmt = $pdo->prepare("UPDATE images SET user_id = ? WHERE user_id != ?");
        $stmt->execute([$user_id, $user_id]);
        $count = $stmt->rowCount();
        echo "<p class='success'>✓ Updated $count images to your account (user_id = $user_id)</p>";
    }
    
    // Option 2: Update specific image
    if (isset($_POST['fix_single'])) {
        $image_id = (int)$_POST['image_id'];
        $stmt = $pdo->prepare("UPDATE images SET user_id = ? WHERE id = ?");
        $stmt->execute([$user_id, $image_id]);
        echo "<p class='success'>✓ Updated image ID $image_id to your account</p>";
    }
    
    // Option 3: Delete images with wrong user_id
    if (isset($_POST['delete_wrong'])) {
        $stmt = $pdo->prepare("DELETE FROM images WHERE user_id != ?");
        $stmt->execute([$user_id]);
        $count = $stmt->rowCount();
        echo "<p class='error'>✓ Deleted $count images with wrong user_id</p>";
    }
    
    echo "<form method='POST' style='margin-top:1rem;'>";
    echo "<button type='submit' name='fix_all' onclick='return confirm(\"This will assign ALL images to your account. Continue?\")' style='padding:0.75rem 1.5rem;background:#7c3aed;color:white;border:none;border-radius:0.5rem;cursor:pointer;margin-right:0.5rem;'>Assign All Images to My Account</button>";
    echo "<button type='submit' name='delete_wrong' onclick='return confirm(\"This will DELETE all images not owned by you. Continue?\")' style='padding:0.75rem 1.5rem;background:#dc2626;color:white;border:none;border-radius:0.5rem;cursor:pointer;'>Delete Images Not Mine</button>";
    echo "</form>";
    
    // Show images with wrong user_id for individual fix
    $wrong_images = $pdo->query("SELECT id, user_id, title FROM images WHERE user_id != $user_id")->fetchAll();
    if (count($wrong_images) > 0) {
        echo "<h3>Fix Individual Images</h3>";
        echo "<form method='POST' style='margin-top:1rem;'><table><tr><th>Select</th><th>ID</th><th>Title</th><th>Current user_id</th></tr>";
        foreach ($wrong_images as $img) {
            echo "<tr><td><input type='radio' name='image_id' value='{$img['id']}'></td><td>{$img['id']}</td><td>{$img['title']}</td><td>{$img['user_id']}</td></tr>";
        }
        echo "</table><button type='submit' name='fix_single' style='margin-top:0.5rem;padding:0.75rem 1.5rem;background:#7c3aed;color:white;border:none;border-radius:0.5rem;cursor:pointer;'>Assign Selected to My Account</button></form>";
    }
    
    echo "<hr>";
    echo "<p><a href='debug.php' style='color:#a78bfa;'>← Back to Debug</a> | <a href='profile.php' style='color:#a78bfa;'>My Profile</a> | <a href='index.php' style='color:#a78bfa;'>Gallery</a></p>";
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
?>
