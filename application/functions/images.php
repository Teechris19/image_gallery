<?php
/**
 * Image handling functions
 * All image-related business logic
 */

// Define APP_ROOT if not already defined
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__, 2));
}

require_once APP_ROOT . '/application/core/config.php';
require_once APP_ROOT . '/application/core/database.php';
require_once APP_ROOT . '/application/functions/general.php';

/**
 * Get all images from database
 * @return array Array of image records
 */
function get_all_images() {
    $pdo = getConnection();
    $stmt = $pdo->query("SELECT * FROM images ORDER BY uploaded_at DESC");
    return $stmt->fetchAll();
}

/**
 * Get a single image by ID
 * @param int $id Image ID
 * @return array|null Image record or null if not found
 */
function get_image_by_id($id) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM images WHERE id = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch();
    return $result ?: null;
}

/**
 * Validate uploaded file
 * @param array $file $_FILES['file'] array
 * @return array ['valid' => bool, 'error' => string]
 */
function validate_upload($file) {
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds server limit',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds form limit',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        ];
        return ['valid' => false, 'error' => $errors[$file['error']] ?? 'Unknown upload error'];
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['valid' => false, 'error' => 'File exceeds maximum size of ' . format_size(MAX_FILE_SIZE)];
    }
    
    // Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime, ALLOWED_TYPES)) {
        return ['valid' => false, 'error' => 'Invalid file type. Allowed: ' . implode(', ', ALLOWED_EXTENSIONS)];
    }
    
    // Check if it's actually an image
    $img_info = getimagesize($file['tmp_name']);
    if ($img_info === false) {
        return ['valid' => false, 'error' => 'File is not a valid image'];
    }
    
    return ['valid' => true, 'error' => '', 'mime' => $mime];
}

/**
 * Upload an image
 * @param array $file $_FILES['file'] array
 * @return array ['success' => bool, 'id' => int|null, 'error' => string]
 */
function upload_image($file) {
    // Validate
    $validation = validate_upload($file);
    if (!$validation['valid']) {
        return ['success' => false, 'id' => null, 'error' => $validation['error']];
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = date('Ymd_') . random_string(8) . '.' . strtolower($extension);
    $target_path = UPLOAD_DIR . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $target_path)) {
        return ['success' => false, 'id' => null, 'error' => 'Failed to save uploaded file'];
    }
    
    // Set proper permissions
    chmod($target_path, 0644);
    
    // Create thumbnail
    create_thumbnail($target_path, THUMB_DIR . $filename, THUMB_WIDTH);
    
    // Save to database
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("INSERT INTO images (filename, original_name, mime_type, size) VALUES (?, ?, ?, ?)");
        $stmt->execute([$filename, $file['name'], $validation['mime'], $file['size']]);
        $id = $pdo->lastInsertId();
        
        return ['success' => true, 'id' => $id, 'error' => ''];
    } catch (PDOException $e) {
        // Clean up file if DB insert fails
        unlink($target_path);
        if (file_exists(THUMB_DIR . $filename)) {
            unlink(THUMB_DIR . $filename);
        }
        error_log("Database error: " . $e->getMessage());
        return ['success' => false, 'id' => null, 'error' => 'Database error occurred'];
    }
}

/**
 * Delete an image
 * @param int $id Image ID
 * @return array ['success' => bool, 'error' => string]
 */
function delete_image($id) {
    // Get image record
    $image = get_image_by_id($id);
    if (!$image) {
        return ['success' => false, 'error' => 'Image not found'];
    }
    
    // Delete files
    $main_file = UPLOAD_DIR . $image['filename'];
    $thumb_file = THUMB_DIR . $image['filename'];
    
    if (file_exists($main_file)) {
        unlink($main_file);
    }
    if (file_exists($thumb_file)) {
        unlink($thumb_file);
    }
    
    // Delete from database
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("DELETE FROM images WHERE id = ?");
        $stmt->execute([$id]);
        
        return ['success' => true, 'error' => ''];
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Database error occurred'];
    }
}

/**
 * Create a thumbnail from an image
 * @param string $source Source image path
 * @param string $dest Destination thumbnail path
 * @param int $width Thumbnail width
 * @return bool Success status
 */
function create_thumbnail($source, $dest, $width = 300) {
    $img_info = getimagesize($source);
    if (!$img_info) {
        return false;
    }
    
    $mime = $img_info['mime'];
    $original_width = $img_info[0];
    $original_height = $img_info[1];
    
    // Calculate height maintaining aspect ratio
    $height = round(($width / $original_width) * $original_height);
    
    // Create new image
    $thumb = imagecreatetruecolor($width, $height);
    
    // Load source image based on type
    switch ($mime) {
        case 'image/jpeg':
            $source_img = imagecreatefromjpeg($source);
            break;
        case 'image/png':
            $source_img = imagecreatefrompng($source);
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
            break;
        case 'image/gif':
            $source_img = imagecreatefromgif($source);
            break;
        case 'image/webp':
            $source_img = imagecreatefromwebp($source);
            break;
        default:
            return false;
    }
    
    if (!$source_img) {
        return false;
    }
    
    // Resize
    imagecopyresampled($thumb, $source_img, 0, 0, 0, 0, $width, $height, $original_width, $original_height);
    imagedestroy($source_img);
    
    // Save thumbnail
    switch ($mime) {
        case 'image/jpeg':
            imagejpeg($thumb, $dest, 85);
            break;
        case 'image/png':
            imagepng($thumb, $dest, 8);
            break;
        case 'image/gif':
            imagegif($thumb, $dest);
            break;
        case 'image/webp':
            imagewebp($thumb, $dest, 85);
            break;
    }
    
    imagedestroy($thumb);
    chmod($dest, 0644);
    
    return true;
}

/**
 * Get image URL
 * @param string $filename
 * @return string
 */
function image_url($filename) {
    return BASE_URL . 'uploads/' . $filename;
}

/**
 * Get thumbnail URL
 * @param string $filename
 * @return string
 */
function thumb_url($filename) {
    return BASE_URL . 'uploads/thumbs/' . $filename;
}

/**
 * Get all categories
 * @return array Array of categories
 */
function get_all_categories() {
    $pdo = getConnection();
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
    return $stmt->fetchAll();
}

/**
 * Get category by ID
 * @param int $id
 * @return array|null
 */
function get_category_by_id($id) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/**
 * Get category by slug
 * @param string $slug
 * @return array|null
 */
function get_category_by_slug($slug) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ?");
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}

/**
 * Add a new category
 * @param string $name
 * @param bool $is_custom
 * @return array ['success' => bool, 'id' => int|null, 'error' => string]
 */
function add_category($name, $is_custom = true) {
    if (empty($name)) {
        return ['success' => false, 'id' => null, 'error' => 'Category name is required'];
    }

    try {
        $pdo = getConnection();
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
        $stmt = $pdo->prepare("INSERT INTO categories (name, slug, is_custom) VALUES (?, ?, ?)");
        $stmt->execute([$name, $slug, $is_custom ? 1 : 0]);
        return ['success' => true, 'id' => $pdo->lastInsertId(), 'error' => ''];
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            return ['success' => false, 'id' => null, 'error' => 'Category already exists'];
        }
        error_log("Add category error: " . $e->getMessage());
        return ['success' => false, 'id' => null, 'error' => 'Failed to add category'];
    }
}

/**
 * Delete a custom category
 * @param int $id
 * @return array ['success' => bool, 'error' => string]
 */
function delete_category($id) {
    try {
        $pdo = getConnection();
        // Check if category is custom
        $stmt = $pdo->prepare("SELECT is_custom FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $category = $stmt->fetch();

        if (!$category) {
            return ['success' => false, 'error' => 'Category not found'];
        }

        if (!$category['is_custom']) {
            return ['success' => false, 'error' => 'Cannot delete default categories'];
        }

        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        return ['success' => true, 'error' => ''];
    } catch (PDOException $e) {
        error_log("Delete category error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to delete category'];
    }
}

/**
 * Get images with filters and search
 * @param array $filters ['category' => string, 'search' => string, 'artist' => string, 'sort' => string]
 * @return array Array of images with user and category info
 */
function get_filtered_images($filters = []) {
    $pdo = getConnection();

    $sql = "SELECT i.*, u.username as artist_username, u.avatar as artist_avatar,
                   c.name as category_name, c.slug as category_slug
            FROM images i
            LEFT JOIN users u ON i.user_id = u.id
            LEFT JOIN categories c ON i.category_id = c.id
            WHERE 1=1";

    $params = [];

    if (!empty($filters['category'])) {
        $sql .= " AND i.category_id = ?";
        $params[] = $filters['category'];
    }

    if (!empty($filters['artist'])) {
        $sql .= " AND u.username = ?";
        $params[] = $filters['artist'];
    }

    if (!empty($filters['search'])) {
        $sql .= " AND (i.title LIKE ? OR i.artist_name LIKE ? OR i.description LIKE ?)";
        $search_term = '%' . $filters['search'] . '%';
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }

    // Sorting
    $sort = $filters['sort'] ?? 'newest';
    switch ($sort) {
        case 'oldest':
            $sql .= " ORDER BY i.uploaded_at ASC";
            break;
        case 'popular':
            $sql .= " ORDER BY (SELECT COUNT(*) FROM likes WHERE image_id = i.id) DESC, i.uploaded_at DESC";
            break;
        case 'views':
            $sql .= " ORDER BY i.views DESC, i.uploaded_at DESC";
            break;
        case 'newest':
        default:
            $sql .= " ORDER BY i.uploaded_at DESC";
            break;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Get image with full details including ratings and likes
 * @param int $id
 * @return array|null
 */
function get_image_full($id) {
    $pdo = getConnection();

    $stmt = $pdo->prepare("
        SELECT i.*, u.username as artist_username, u.avatar as artist_avatar, 
               u.bio as artist_bio, u.location as artist_location, u.portfolio_url,
               c.name as category_name, c.slug as category_slug
        FROM images i
        LEFT JOIN users u ON i.user_id = u.id
        LEFT JOIN categories c ON i.category_id = c.id
        WHERE i.id = ?
    ");
    $stmt->execute([$id]);
    $image = $stmt->fetch();

    if (!$image) {
        return null;
    }

    // Get average rating
    $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as rating_count FROM ratings WHERE image_id = ?");
    $stmt->execute([$id]);
    $rating_data = $stmt->fetch();
    $image['avg_rating'] = round($rating_data['avg_rating'] ?? 0, 1);
    $image['rating_count'] = $rating_data['rating_count'] ?? 0;

    // Get like count
    $stmt = $pdo->prepare("SELECT COUNT(*) as like_count FROM likes WHERE image_id = ?");
    $stmt->execute([$id]);
    $image['like_count'] = $stmt->fetch()['like_count'] ?? 0;

    return $image;
}

/**
 * Rate an image
 * @param int $image_id
 * @param int $user_id
 * @param int $rating (1-5)
 * @return array ['success' => bool, 'error' => string]
 */
function rate_image($image_id, $user_id, $rating) {
    if ($rating < 1 || $rating > 5) {
        return ['success' => false, 'error' => 'Rating must be between 1 and 5'];
    }

    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO ratings (image_id, user_id, rating) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE rating = VALUES(rating)
        ");
        $stmt->execute([$image_id, $user_id, $rating]);
        return ['success' => true, 'error' => ''];
    } catch (PDOException $e) {
        error_log("Rate image error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to submit rating'];
    }
}

/**
 * Get user's rating for an image
 * @param int $image_id
 * @param int $user_id
 * @return int|null
 */
function get_user_rating($image_id, $user_id) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT rating FROM ratings WHERE image_id = ? AND user_id = ?");
    $stmt->execute([$image_id, $user_id]);
    $result = $stmt->fetch();
    return $result ? $result['rating'] : null;
}

/**
 * Like an image
 * @param int $image_id
 * @param int $user_id
 * @return array ['success' => bool, 'error' => string, 'action' => string]
 */
function like_image($image_id, $user_id) {
    try {
        $pdo = getConnection();

        // Check if already liked
        $stmt = $pdo->prepare("SELECT id FROM likes WHERE image_id = ? AND user_id = ?");
        $stmt->execute([$image_id, $user_id]);

        if ($stmt->fetch()) {
            // Unlike
            $stmt = $pdo->prepare("DELETE FROM likes WHERE image_id = ? AND user_id = ?");
            $stmt->execute([$image_id, $user_id]);
            return ['success' => true, 'error' => '', 'action' => 'unliked'];
        } else {
            // Like
            $stmt = $pdo->prepare("INSERT INTO likes (image_id, user_id) VALUES (?, ?)");
            $stmt->execute([$image_id, $user_id]);
            return ['success' => true, 'error' => '', 'action' => 'liked'];
        }
    } catch (PDOException $e) {
        error_log("Like image error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to process like', 'action' => ''];
    }
}

/**
 * Check if user has liked an image
 * @param int $image_id
 * @param int $user_id
 * @return bool
 */
function has_liked($image_id, $user_id) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT id FROM likes WHERE image_id = ? AND user_id = ?");
    $stmt->execute([$image_id, $user_id]);
    return (bool) $stmt->fetch();
}

/**
 * Get user's liked images
 * @param int $user_id
 * @return array
 */
function get_user_liked_images($user_id) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("
        SELECT i.*, u.username as artist_username, u.avatar as artist_avatar,
               c.name as category_name, c.slug as category_slug
        FROM images i
        INNER JOIN likes l ON i.id = l.image_id
        LEFT JOIN users u ON i.user_id = u.id
        LEFT JOIN categories c ON i.category_id = c.id
        WHERE l.user_id = ?
        ORDER BY l.created_at DESC
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

/**
 * Get user's uploaded images
 * @param int $user_id
 * @return array
 */
function get_user_images($user_id) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("
        SELECT i.*, c.name as category_name, c.slug as category_slug
        FROM images i
        LEFT JOIN categories c ON i.category_id = c.id
        WHERE i.user_id = ?
        ORDER BY i.uploaded_at DESC
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

/**
 * Get artist's images
 * @param string $username
 * @return array
 */
function get_artist_images($username) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("
        SELECT i.*, c.name as category_name, c.slug as category_slug
        FROM images i
        INNER JOIN users u ON i.user_id = u.id
        LEFT JOIN categories c ON i.category_id = c.id
        WHERE u.username = ?
        ORDER BY i.uploaded_at DESC
    ");
    $stmt->execute([$username]);
    return $stmt->fetchAll();
}

/**
 * Increment image view count
 * @param int $id
 */
function increment_views($id) {
    try {
        $pdo = getConnection();
        $pdo->prepare("UPDATE images SET views = views + 1 WHERE id = ?")->execute([$id]);
    } catch (PDOException $e) {
        error_log("Increment views error: " . $e->getMessage());
    }
}

/**
 * Track image download
 * @param int $image_id
 * @param int $user_id
 * @return array ['success' => bool, 'error' => string]
 */
function track_download($image_id, $user_id) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO downloads (user_id, image_id)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE downloaded_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([$user_id, $image_id]);
        return ['success' => true, 'error' => ''];
    } catch (PDOException $e) {
        error_log("Track download error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to track download'];
    }
}

/**
 * Get user's downloaded images
 * @param int $user_id
 * @return array
 */
function get_user_downloads($user_id) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("
        SELECT i.*, u.username as artist_username, u.avatar as artist_avatar,
               c.name as category_name, c.slug as category_slug,
               d.downloaded_at
        FROM downloads d
        INNER JOIN images i ON d.image_id = i.id
        LEFT JOIN users u ON i.user_id = u.id
        LEFT JOIN categories c ON i.category_id = c.id
        WHERE d.user_id = ?
        ORDER BY d.downloaded_at DESC
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

/**
 * Check if user has downloaded an image
 * @param int $image_id
 * @param int $user_id
 * @return bool
 */
function has_downloaded($image_id, $user_id) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT id FROM downloads WHERE image_id = ? AND user_id = ?");
    $stmt->execute([$image_id, $user_id]);
    return (bool) $stmt->fetch();
}
