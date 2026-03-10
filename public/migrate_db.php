<?php
/**
 * Database Migration Script
 * Run this file once in your browser to update the database schema
 * Access via: http://localhost/image-gallery/public/migrate_db.php
 */

define('APP_ROOT', dirname(__DIR__));

require_once APP_ROOT . '/application/core/config.php';
require_once APP_ROOT . '/application/core/database.php';

echo "<!DOCTYPE html><html><head><title>Database Migration</title>";
echo "<style>body{font-family:monospace;background:#1e293b;color:#e2e8f0;padding:2rem;max-width:800px;margin:0 auto;}";
echo ".success{color:#4ade80;} .error{color:#f87171;} .info{color:#60a5fa;}</style></head><body>";
echo "<h1>Database Migration</h1>";

try {
    $pdo = getConnection();
    $dbname = DB_NAME;
    
    echo "<p class='info'>Connected to database: $dbname</p>";
    
    // Check and add user_id column
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE table_schema = '$dbname' AND table_name = 'images' AND column_name = 'user_id'");
    if ($stmt->fetch()['cnt'] == 0) {
        $pdo->exec("ALTER TABLE images ADD COLUMN user_id INT NOT NULL DEFAULT 1 AFTER id");
        echo "<p class='success'>✓ Added user_id column to images table</p>";
    } else {
        echo "<p class='info'>✓ user_id column already exists</p>";
    }
    
    // Check and add category_id column
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE table_schema = '$dbname' AND table_name = 'images' AND column_name = 'category_id'");
    if ($stmt->fetch()['cnt'] == 0) {
        $pdo->exec("ALTER TABLE images ADD COLUMN category_id INT DEFAULT NULL AFTER user_id");
        echo "<p class='success'>✓ Added category_id column to images table</p>";
    } else {
        echo "<p class='info'>✓ category_id column already exists</p>";
    }
    
    // Check and add artist_name column
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE table_schema = '$dbname' AND table_name = 'images' AND column_name = 'artist_name'");
    if ($stmt->fetch()['cnt'] == 0) {
        $pdo->exec("ALTER TABLE images ADD COLUMN artist_name VARCHAR(100) NOT NULL DEFAULT 'Unknown' AFTER category_id");
        echo "<p class='success'>✓ Added artist_name column to images table</p>";
    } else {
        echo "<p class='info'>✓ artist_name column already exists</p>";
    }
    
    // Create ratings table
    $pdo->exec("CREATE TABLE IF NOT EXISTS ratings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        image_id INT NOT NULL,
        rating TINYINT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE,
        UNIQUE KEY unique_rating (user_id, image_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "<p class='success'>✓ Ratings table created/verified</p>";
    
    // Create follows table
    $pdo->exec("CREATE TABLE IF NOT EXISTS follows (
        id INT PRIMARY KEY AUTO_INCREMENT,
        follower_id INT NOT NULL,
        following_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_follow (follower_id, following_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "<p class='success'>✓ Follows table created/verified</p>";
    
    // Add is_custom to categories
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE table_schema = '$dbname' AND table_name = 'categories' AND column_name = 'is_custom'");
    if ($stmt->fetch()['cnt'] == 0) {
        $pdo->exec("ALTER TABLE categories ADD COLUMN is_custom TINYINT(1) DEFAULT 0 AFTER slug");
        echo "<p class='success'>✓ Added is_custom column to categories table</p>";
    } else {
        echo "<p class='info'>✓ is_custom column already exists</p>";
    }
    
    echo "<hr><p class='success'><strong>Migration completed successfully!</strong></p>";
    echo "<p><a href='index.php' style='color:#a78bfa;'>← Back to Gallery</a></p>";
    
} catch (PDOException $e) {
    echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
?>
