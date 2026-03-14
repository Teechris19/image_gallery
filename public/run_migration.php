<?php
/**
 * Database Migration Script - Adds downloads table
 */

define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/application/core/config.php';
require_once APP_ROOT . '/application/core/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Migration</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #1e293b; color: #e2e8f0; }
        .success { background: rgba(34, 197, 94, 0.2); border: 1px solid #22c55e; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .error { background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .info { background: rgba(59, 130, 246, 0.2); border: 1px solid #3b82f6; padding: 15px; border-radius: 8px; margin: 10px 0; }
        h1 { color: #a78bfa; }
        a { color: #a78bfa; }
    </style>
</head>
<body>
    <h1>Database Migration - Downloads Table</h1>";

try {
    $pdo = getConnection();
    
    // Create downloads table
    $sql = "CREATE TABLE IF NOT EXISTS downloads (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        image_id INT NOT NULL,
        downloaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE,
        UNIQUE KEY unique_download (user_id, image_id),
        INDEX idx_user_id (user_id),
        INDEX idx_downloaded_at (downloaded_at DESC)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    
    echo "<div class='success'>
        <strong>✓ Success!</strong><br>
        The <code>downloads</code> table has been created successfully.
    </div>";
    
    // Verify table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'downloads'");
    if ($stmt->fetch()) {
        echo "<div class='info'>
            <strong>Table Structure:</strong><br>
            <code>downloads</code> table is ready to use.
        </div>";
    }
    
    echo "<div class='info'>
        <strong>Next Steps:</strong><br>
        1. <a href='index.php'>Go to Gallery</a><br>
        2. <a href='downloads.php'>View Downloads Page</a><br>
        3. Download an image to test the feature
    </div>";
    
} catch (PDOException $e) {
    echo "<div class='error'>
        <strong>✗ Error:</strong><br>
        " . e($e->getMessage()) . "
    </div>";
}

echo "<p><a href='index.php' style='color: #a78bfa;'>← Back to Gallery</a></p>
</body>
</html>";
?>
