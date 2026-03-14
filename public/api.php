<?php
/**
 * API Endpoints for AJAX interactions
 */

define('APP_ROOT', dirname(__DIR__));

require_once APP_ROOT . '/application/core/config.php';
require_once APP_ROOT . '/application/core/database.php';
require_once APP_ROOT . '/application/functions/general.php';
require_once APP_ROOT . '/application/functions/images.php';
require_once APP_ROOT . '/application/functions/auth.php';

// Initialize database
initializeDatabase();

// Start session once at the beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set JSON response header
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get action from request
$action = get_input('action');

// Handle different actions
switch ($action) {
    case 'login':
        handle_login();
        break;

    case 'register':
        handle_register();
        break;

    case 'like':
        handle_like();
        break;

    case 'rate':
        handle_rate();
        break;

    case 'follow':
        handle_follow();
        break;

    case 'unfollow':
        handle_unfollow();
        break;

    case 'search':
        handle_search();
        break;

    case 'search_suggestions':
        handle_search_suggestions();
        break;

    case 'add_category':
        handle_add_category();
        break;

    case 'delete_category':
        handle_delete_category();
        break;

    case 'upload_image':
        handle_upload_image();
        break;

    case 'upload_profile_image':
        handle_upload_profile_image();
        break;

    case 'get_image_details':
        handle_get_image_details();
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}

/**
 * Handle user login
 */
function handle_login() {
    $username = get_input('username');
    $password = get_input('password');

    if (!$username || !$password) {
        echo json_encode(['success' => false, 'error' => 'Username and password are required']);
        return;
    }

    $result = login_user($username, $password);

    if ($result['success']) {
        start_user_session($result['user']);
        echo json_encode(['success' => true, 'user' => $result['user']]);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => $result['error']]);
    }
}

/**
 * Handle user registration
 */
function handle_register() {
    $username = get_input('username');
    $email = get_input('email');
    $password = get_input('password');

    if (!$username || !$email || !$password) {
        echo json_encode(['success' => false, 'error' => 'All fields are required']);
        return;
    }

    $result = register_user($username, $email, $password);

    if ($result['success']) {
        $user = get_user_by_id($result['user_id']);
        start_user_session($user);
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $result['error']]);
    }
}

/**
 * Handle getting image details
 */
function handle_get_image_details() {
    $image_id = get_input('image_id');
    if (!$image_id) {
        echo json_encode(['success' => false, 'error' => 'Image ID required']);
        return;
    }

    $image = get_image_full($image_id);
    if (!$image) {
        echo json_encode(['success' => false, 'error' => 'Image not found']);
        return;
    }

    $user_id = get_logged_in_user_id();
    $is_liked = $user_id ? has_liked($image_id, $user_id) : false;
    $user_rating = $user_id ? get_user_rating($image_id, $user_id) : null;

    echo json_encode([
        'success' => true,
        'image' => [
            'id' => $image['id'],
            'title' => $image['title'],
            'description' => $image['description'],
            'artist_name' => $image['artist_name'],
            'artist_username' => $image['artist_username'],
            'category_name' => $image['category_name'],
            'uploaded_at' => $image['uploaded_at'],
            'views' => $image['views'],
            'avg_rating' => $image['avg_rating'],
            'rating_count' => $image['rating_count'],
            'like_count' => $image['like_count'],
            'is_liked' => $is_liked,
            'user_rating' => $user_rating
        ]
    ]);
}

/**
 * Handle like/unlike action
 */
function handle_like() {
    if (!is_logged_in()) {
        echo json_encode(['success' => false, 'error' => 'Please login to like images', 'requires_auth' => true]);
        return;
    }

    $image_id = get_input('image_id');
    if (!$image_id) {
        echo json_encode(['success' => false, 'error' => 'Image ID required']);
        return;
    }

    $user_id = get_logged_in_user_id();
    if (!$user_id) {
        echo json_encode(['success' => false, 'error' => 'User not logged in', 'requires_auth' => true]);
        return;
    }

    $result = like_image($image_id, $user_id);

    if ($result['success']) {
        $image = get_image_full($image_id);
        echo json_encode([
            'success' => true,
            'action' => $result['action'],
            'like_count' => $image['like_count'],
            'liked' => has_liked($image_id, $user_id)
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => $result['error']]);
    }
}

/**
 * Handle rating action
 */
function handle_rate() {
    if (!is_logged_in()) {
        echo json_encode(['success' => false, 'error' => 'Please login to rate images', 'requires_auth' => true]);
        return;
    }

    $image_id = get_input('image_id');
    $rating = (int) get_input('rating');

    if (!$image_id) {
        echo json_encode(['success' => false, 'error' => 'Image ID required']);
        return;
    }

    if ($rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'error' => 'Rating must be between 1 and 5']);
        return;
    }

    $user_id = get_logged_in_user_id();
    if (!$user_id) {
        echo json_encode(['success' => false, 'error' => 'User not logged in', 'requires_auth' => true]);
        return;
    }

    $result = rate_image($image_id, $user_id, $rating);

    if ($result['success']) {
        $image = get_image_full($image_id);
        echo json_encode([
            'success' => true,
            'avg_rating' => $image['avg_rating'],
            'rating_count' => $image['rating_count'],
            'user_rating' => $rating
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => $result['error']]);
    }
}

/**
 * Handle follow action
 */
function handle_follow() {
    if (!is_logged_in()) {
        echo json_encode(['success' => false, 'error' => 'Please login to follow artists', 'requires_auth' => true]);
        return;
    }

    $following_id = get_input('user_id');
    if (!$following_id) {
        echo json_encode(['success' => false, 'error' => 'User ID required']);
        return;
    }

    $follower_id = get_logged_in_user_id();
    if (!$follower_id) {
        echo json_encode(['success' => false, 'error' => 'User not logged in', 'requires_auth' => true]);
        return;
    }

    $result = follow_user($follower_id, $following_id);

    if ($result['success']) {
        $stats = get_user_stats($following_id);
        echo json_encode([
            'success' => true,
            'following' => true,
            'followers' => $stats['followers']
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => $result['error']]);
    }
}

/**
 * Handle unfollow action
 */
function handle_unfollow() {
    if (!is_logged_in()) {
        echo json_encode(['success' => false, 'error' => 'Please login to unfollow artists', 'requires_auth' => true]);
        return;
    }

    $following_id = get_input('user_id');
    if (!$following_id) {
        echo json_encode(['success' => false, 'error' => 'User ID required']);
        return;
    }

    $follower_id = get_logged_in_user_id();
    if (!$follower_id) {
        echo json_encode(['success' => false, 'error' => 'User not logged in', 'requires_auth' => true]);
        return;
    }

    $result = unfollow_user($follower_id, $following_id);

    if ($result['success']) {
        $stats = get_user_stats($following_id);
        echo json_encode([
            'success' => true,
            'following' => false,
            'followers' => $stats['followers']
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => $result['error']]);
    }
}

/**
 * Handle search action
 */
function handle_search() {
    $search = get_input('query', '');
    $category = get_input('category', '');
    $artist = get_input('artist', '');

    $filters = [];
    if ($search) $filters['search'] = $search;
    if ($category) $filters['category'] = $category;
    if ($artist) $filters['artist'] = $artist;

    $images = get_filtered_images($filters);

    $formatted_images = [];
    foreach ($images as $image) {
        $formatted_images[] = [
            'id' => $image['id'],
            'title' => $image['title'],
            'artist_name' => $image['artist_name'],
            'artist_username' => $image['artist_username'],
            'category_name' => $image['category_name'],
            'thumbnail_url' => thumb_url($image['filename']),
            'image_url' => image_url($image['filename'])
        ];
    }

    echo json_encode([
        'success' => true,
        'count' => count($formatted_images),
        'images' => $formatted_images
    ]);
}

/**
 * Handle search suggestions
 */
function handle_search_suggestions() {
    $query = get_input('query', '');
    
    if (strlen($query) < 2) {
        echo json_encode(['success' => true, 'suggestions' => []]);
        return;
    }

    $pdo = getConnection();
    $suggestions = [];
    $searchTerm = '%' . $query . '%';

    // Search for artists
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE username LIKE ? LIMIT 5");
    $stmt->execute([$searchTerm]);
    $artists = $stmt->fetchAll();
    foreach ($artists as $artist) {
        $suggestions[] = [
            'type' => 'artist',
            'value' => $artist['username'],
            'label' => $artist['username']
        ];
    }

    // Search for categories
    $stmt = $pdo->prepare("SELECT id, name, slug FROM categories WHERE name LIKE ? LIMIT 3");
    $stmt->execute([$searchTerm]);
    $categories = $stmt->fetchAll();
    foreach ($categories as $cat) {
        $suggestions[] = [
            'type' => 'category',
            'value' => $cat['slug'],
            'label' => $cat['name']
        ];
    }

    // Search for artworks
    $stmt = $pdo->prepare("SELECT id, title FROM images WHERE title LIKE ? LIMIT 3");
    $stmt->execute([$searchTerm]);
    $artworks = $stmt->fetchAll();
    foreach ($artworks as $artwork) {
        $suggestions[] = [
            'type' => 'artwork',
            'value' => $artwork['title'],
            'label' => $artwork['title']
        ];
    }

    echo json_encode([
        'success' => true,
        'suggestions' => $suggestions
    ]);
}

/**
 * Handle add category action
 */
function handle_add_category() {
    if (!is_logged_in()) {
        echo json_encode(['success' => false, 'error' => 'Please login to add categories', 'requires_auth' => true]);
        return;
    }

    $name = get_input('name');
    if (!$name) {
        echo json_encode(['success' => false, 'error' => 'Category name required']);
        return;
    }

    $result = add_category($name, true);

    if ($result['success']) {
        $category = get_category_by_id($result['id']);
        echo json_encode([
            'success' => true,
            'category' => $category
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => $result['error']]);
    }
}

/**
 * Handle delete category action
 */
function handle_delete_category() {
    if (!is_logged_in()) {
        echo json_encode(['success' => false, 'error' => 'Please login to delete categories', 'requires_auth' => true]);
        return;
    }

    $id = get_input('id');
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'Category ID required']);
        return;
    }

    $result = delete_category($id);

    if ($result['success']) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $result['error']]);
    }
}

/**
 * Handle image upload via AJAX
 */
function handle_upload_image() {
    // Check authentication first
    if (!is_logged_in()) {
        echo json_encode(['success' => false, 'error' => 'Please login to upload images', 'requires_auth' => true]);
        return;
    }

    $user = get_logged_in_user();
    $user_id = get_logged_in_user_id();

    if (!$user_id || !$user) {
        echo json_encode(['success' => false, 'error' => 'User not authenticated. Please login again.', 'requires_auth' => true]);
        return;
    }

    if (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
        echo json_encode(['success' => false, 'error' => 'No image selected']);
        return;
    }

    $title = get_input('title', $_FILES['image']['name']);
    $description = get_input('description', '');
    $category_id = get_input('category_id', null);
    $artist_name = get_input('artist_name', $user['username']);

    // Validate file
    $validation = validate_upload($_FILES['image']);
    if (!$validation['valid']) {
        echo json_encode(['success' => false, 'error' => $validation['error']]);
        return;
    }

    // Ensure upload directories exist
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }
    if (!is_dir(THUMB_DIR)) {
        mkdir(THUMB_DIR, 0755, true);
    }

    // Check if directories are writable
    if (!is_writable(UPLOAD_DIR)) {
        echo json_encode(['success' => false, 'error' => 'Upload directory is not writable. Please check permissions.']);
        return;
    }

    // Generate unique filename
    $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $filename = date('Ymd_') . random_string(8) . '.' . strtolower($extension);
    $target_path = UPLOAD_DIR . $filename;

    // Move uploaded file
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
        echo json_encode(['success' => false, 'error' => 'Failed to save uploaded file. Check directory permissions.']);
        return;
    }

    chmod($target_path, 0644);

    // Create thumbnail
    if (!create_thumbnail($target_path, THUMB_DIR . $filename, THUMB_WIDTH)) {
        error_log("Failed to create thumbnail for: $filename");
    }

    // Save to database
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO images (user_id, category_id, artist_name, title, description, filename, original_name, mime_type, size)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user_id,
            $category_id ?: null,
            $artist_name,
            $title,
            $description,
            $filename,
            $_FILES['image']['name'],
            $validation['mime'],
            $_FILES['image']['size']
        ]);
        $id = $pdo->lastInsertId();

        // Get full image data
        $image = get_image_full($id);

        echo json_encode([
            'success' => true,
            'id' => $id,
            'image' => [
                'id' => $image['id'],
                'title' => $image['title'],
                'artist_name' => $image['artist_name'],
                'thumbnail_url' => thumb_url($filename),
                'image_url' => image_url($filename)
            ]
        ]);
    } catch (PDOException $e) {
        // Clean up files if DB insert fails
        if (file_exists($target_path)) {
            unlink($target_path);
        }
        $thumb_path = THUMB_DIR . $filename;
        if (file_exists($thumb_path)) {
            unlink($thumb_path);
        }
        $error_msg = $e->getMessage();
        error_log("Upload database error: " . $error_msg);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $error_msg]);
    }
}

/**
 * Handle profile image upload via AJAX
 */
function handle_upload_profile_image() {
    if (!is_logged_in()) {
        echo json_encode(['success' => false, 'error' => 'Please login to upload profile image', 'requires_auth' => true]);
        return;
    }

    $user = get_logged_in_user();
    $user_id = get_logged_in_user_id();

    if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] === UPLOAD_ERR_NO_FILE) {
        echo json_encode(['success' => false, 'error' => 'No image selected']);
        return;
    }

    // Validate file
    $validation = validate_upload($_FILES['profile_image']);
    if (!$validation['valid']) {
        echo json_encode(['success' => false, 'error' => $validation['error']]);
        return;
    }

    // Ensure upload directories exist
    $profile_dir = UPLOAD_DIR . 'profiles/';
    if (!is_dir($profile_dir)) {
        mkdir($profile_dir, 0755, true);
    }

    // Generate unique filename
    $extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
    $filename = 'profile_' . $user_id . '_' . time() . '.' . strtolower($extension);
    $target_path = $profile_dir . $filename;

    // Move uploaded file
    if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_path)) {
        echo json_encode(['success' => false, 'error' => 'Failed to save uploaded file']);
        return;
    }

    chmod($target_path, 0644);

    // Update user's avatar in database
    try {
        $pdo = getConnection();
        
        // Delete old profile image if exists
        if ($user['avatar']) {
            $old_path = UPLOAD_DIR . 'profiles/' . basename($user['avatar']);
            if (file_exists($old_path)) {
                unlink($old_path);
            }
        }
        
        // Update avatar
        $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        $stmt->execute([$filename, $user_id]);
        
        // Update session
        $_SESSION['user']['avatar'] = $filename;
        
        echo json_encode([
            'success' => true,
            'avatar_url' => BASE_URL . 'uploads/profiles/' . $filename
        ]);
    } catch (PDOException $e) {
        if (file_exists($target_path)) {
            unlink($target_path);
        }
        error_log("Profile upload database error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}
