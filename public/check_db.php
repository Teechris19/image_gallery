<?php
/**
 * Database Check Script
 * Shows current database schema status
 */

define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/application/core/config.php';
require_once APP_ROOT . '/application/core/database.php';

echo "<!DOCTYPE html><html><head><title>Database Check</title>";
echo "<style>body{font-family:monospace;background:#1e293b;color:#e2e8f0;padding:2rem;max-width:1000px;margin:0 auto;}";
echo ".success{color:#4ade80;} .error{color:#f87171;} .info{color:#60a5fa;} table{width:100%;border-collapse:collapse;margin-top:1rem;}";
echo "th,td{border:1px solid #475569;padding:0.5rem;text-align:left;} th{background:#334155;}</style></head><body>";
echo "<h1>Database Schema Check</h1>";

try {
    $pdo = getConnection();
    $dbname = DB_NAME;
    
    echo "<p class='success'>✓ Connected to database: <strong>$dbname</strong></p>";
    
    // Check tables
    $tables = ['users', 'images', 'categories', 'ratings', 'likes', 'follows'];
    echo "<h2>Tables</h2><table><tr><th>Table</th><th>Status</th></tr>";
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->rowCount() > 0;
        echo "<tr><td>$table</td><td class='" . ($exists ? 'success' : 'error') . "'>" . ($exists ? '✓ Exists' : '✗ Missing') . "</td></tr>";
    }
    echo "</table>";
    
    // Check images table columns
    echo "<h2>Images Table Columns</h2><table><tr><th>Column</th><th>Type</th><th>Nullable</th></tr>";
    $stmt = $pdo->query("SHOW COLUMNS FROM images");
    while ($col = $stmt->fetch()) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>" . ($col['Null'] === 'YES' ? 'Yes' : 'No') . "</td></tr>";
    }
    echo "</table>";
    
    // Check if user_id column exists
    echo "<h2>Column Checks</h2><table><tr><th>Check</th><th>Status</th></tr>";
    
    $checks = [
        ['table' => 'images', 'column' => 'user_id'],
        ['table' => 'images', 'column' => 'category_id'],
        ['table' => 'images', 'column' => 'artist_name'],
        ['table' => 'images', 'column' => 'title'],
        ['table' => 'images', 'column' => 'description'],
        ['table' => 'categories', 'column' => 'is_custom'],
    ];
    
    foreach ($checks as $check) {
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE table_schema = '$dbname' AND table_name = '{$check['table']}' AND column_name = '{$check['column']}'");
        $exists = $stmt->fetch()['cnt'] > 0;
        echo "<tr><td>{$check['table']}.{$check['column']}</td><td class='" . ($exists ? 'success' : 'error') . "'>" . ($exists ? '✓ Exists' : '✗ Missing') . "</td></tr>";
    }
    echo "</table>";
    
    // Test insert
    echo "<h2>Test Upload</h2>";
    echo "<form method='POST' enctype='multipart/form-data' style='margin-top:1rem;'>";
    echo "<input type='file' name='test_image' accept='image/*' required style='padding:0.5rem;background:#334155;border:1px solid #475569;color:white;border-radius:0.5rem;'>";
    echo "<button type='submit' name='test_upload' style='margin-left:0.5rem;padding:0.5rem 1rem;background:#7c3aed;color:white;border:none;border-radius:0.5rem;cursor:pointer;'>Test Upload</button>";
    echo "</form>";
    
    if (isset($_POST['test_upload']) && isset($_FILES['test_image'])) {
        $file = $_FILES['test_image'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            try {
                $stmt = $pdo->prepare("INSERT INTO images (user_id, artist_name, title, filename, original_name, mime_type, size) VALUES (1, 'Test', 'Test Image', ?, ?, ?, ?)");
                $filename = 'test_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
                $stmt->execute([$filename, $file['name'], $file['type'], $file['size']]);
                echo "<p class='success'>✓ Test insert successful! ID: " . $pdo->lastInsertId() . "</p>";
            } catch (PDOException $e) {
                echo "<p class='error'>✗ Test insert failed: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr><p><a href='index.php' style='color:#a78bfa;'>← Back to Gallery</a> | <a href='migrate_db.php' style='color:#a78bfa;'>Run Migration</a></p>";
echo "</body></html>";
?>
