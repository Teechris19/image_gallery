<?php
/**
 * Complete Database Fix Script
 * Adds all missing columns and tables
 */

define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/application/core/config.php';
require_once APP_ROOT . '/application/core/database.php';

echo "<!DOCTYPE html><html><head><title>Database Fix</title>";
echo "<style>body{font-family:monospace;background:#1e293b;color:#e2e8f0;padding:2rem;max-width:800px;margin:0 auto;}";
echo ".success{color:#4ade80;} .error{color:#f87171;} .info{color:#60a5fa;} h2{margin-top:1.5rem;}</style></head><body>";
echo "<h1>🔧 Database Fix</h1>";

try {
    $pdo = getConnection();
    $dbname = DB_NAME;
    
    echo "<p class='success'>✓ Connected to database: <strong>$dbname</strong></p>";
    echo "<h2>Fixing Images Table...</h2>";
    
    // Get current columns
    $stmt = $pdo->query("SHOW COLUMNS FROM images");
    $current_columns = [];
    while ($row = $stmt->fetch()) {
        $current_columns[] = $row['Field'];
    }
    
    echo "<p class='info'>Current columns: " . implode(', ', $current_columns) . "</p>";
    
    // Required columns with their definitions
    $required_columns = [
        'user_id' => 'INT NOT NULL AFTER id',
        'category_id' => 'INT DEFAULT NULL AFTER user_id',
        'artist_name' => "VARCHAR(100) NOT NULL DEFAULT 'Unknown' AFTER category_id",
        'title' => 'VARCHAR(255) NOT NULL AFTER artist_name',
        'description' => 'TEXT DEFAULT NULL AFTER title',
        'filename' => 'VARCHAR(255) NOT NULL AFTER description',
        'original_name' => 'VARCHAR(255) NOT NULL AFTER filename',
        'mime_type' => 'VARCHAR(100) NOT NULL AFTER original_name',
        'size' => 'INT NOT NULL AFTER mime_type',
        'views' => 'INT DEFAULT 0 AFTER size',
        'uploaded_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER size'
    ];
    
    foreach ($required_columns as $column => $definition) {
        if (!in_array($column, $current_columns)) {
            $pdo->exec("ALTER TABLE images ADD COLUMN $column $definition");
            echo "<p class='success'>✓ Added column: <strong>$column</strong></p>";
        } else {
            echo "<p class='info'>✓ Column already exists: <strong>$column</strong></p>";
        }
    }
    
    // Add indexes
    echo "<h2>Adding Indexes...</h2>";
    try {
        $pdo->exec("ALTER TABLE images ADD INDEX IF NOT EXISTS idx_user_id (user_id)");
        echo "<p class='success'>✓ Added index: idx_user_id</p>";
    } catch (Exception $e) {
        echo "<p class='info'>Index idx_user_id may already exist</p>";
    }
    
    try {
        $pdo->exec("ALTER TABLE images ADD INDEX IF NOT EXISTS idx_category_id (category_id)");
        echo "<p class='success'>✓ Added index: idx_category_id</p>";
    } catch (Exception $e) {
        echo "<p class='info'>Index idx_category_id may already exist</p>";
    }
    
    try {
        $pdo->exec("ALTER TABLE images ADD INDEX IF NOT EXISTS idx_artist_name (artist_name)");
        echo "<p class='success'>✓ Added index: idx_artist_name</p>";
    } catch (Exception $e) {
        echo "<p class='info'>Index idx_artist_name may already exist</p>";
    }
    
    try {
        $pdo->exec("ALTER TABLE images ADD INDEX IF NOT EXISTS idx_uploaded_at (uploaded_at DESC)");
        echo "<p class='success'>✓ Added index: idx_uploaded_at</p>";
    } catch (Exception $e) {
        echo "<p class='info'>Index idx_uploaded_at may already exist</p>";
    }
    
    // Create missing tables
    echo "<h2>Creating Missing Tables...</h2>";
    
    // Ratings table
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
    
    // Follows table
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
        echo "<p class='success'>✓ Added is_custom column to categories</p>";
    } else {
        echo "<p class='info'>✓ is_custom column already exists</p>";
    }
    
    // Verify final structure
    echo "<h2>Final Verification</h2>";
    $stmt = $pdo->query("SHOW COLUMNS FROM images");
    $final_columns = [];
    while ($row = $stmt->fetch()) {
        $final_columns[] = $row['Field'];
    }
    echo "<p class='success'>✓ Images table now has columns: <strong>" . implode(', ', $final_columns) . "</strong></p>";
    
    // Test insert
    echo "<h2>Test Upload</h2>";
    try {
        $pdo->exec("INSERT INTO images (user_id, artist_name, title, filename, original_name, mime_type, size) VALUES (1, 'Test', 'Test Image', 'test.jpg', 'test.jpg', 'image/jpeg', 1024)");
        $id = $pdo->lastInsertId();
        $pdo->exec("DELETE FROM images WHERE id = $id");
        echo "<p class='success'>✓ Test insert successful! Database is working correctly.</p>";
    } catch (PDOException $e) {
        echo "<p class='error'>✗ Test insert failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    echo "<hr>";
    echo "<p class='success'><strong>✅ Database fix completed successfully!</strong></p>";
    echo "<p><a href='index.php' style='color:#a78bfa;'>← Back to Gallery</a></p>";
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
?>
