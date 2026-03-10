<?php
/**
 * API Endpoints for AJAX interactions
 * Handles like, rate, follow, search, and category management
 */

define('APP_ROOT', dirname(__DIR__));

require_once APP_ROOT . '/application/core/config.php';
require_once APP_ROOT . '/application/core/database.php';
require_once APP_ROOT . '/application/functions/general.php';
require_once APP_ROOT . '/application/functions/images.php';
require_once APP_ROOT . '/application/functions/auth.php';

// Initialize database
initializeDatabase();

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

    case 'add_category':
        handle_add_category();
        break;

    case 'delete_category':
        handle_delete_category();
        break;

    case 'upload_image':
        handle_upload_image();
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
        // Auto-login after registration
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

    session_start();
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
    session_start();

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
    $result = like_image($image_id, $user_id);

    if ($result['success']) {
        // Get updated like count
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
    session_start();

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
    $result = rate_image($image_id, $user_id, $rating);

    if ($result['success']) {
        // Get updated rating data
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
    session_start();

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
    $result = follow_user($follower_id, $following_id);

    if ($result['success']) {
        // Get updated follower count
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
    session_start();

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
    $result = unfollow_user($follower_id, $following_id);

    if ($result['success']) {
        // Get updated follower count
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

    // Format images for response
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
 * Handle add category action
 */
function handle_add_category() {
    session_start();

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
    session_start();

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
    session_start();

    if (!is_logged_in()) {
        echo json_encode(['success' => false, 'error' => 'Please login to upload images', 'requires_auth' => true]);
        return;
    }

    if (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
        echo json_encode(['success' => false, 'error' => 'No image selected']);
        return;
    }

    $title = get_input('title', $_FILES['image']['name']);
    $description = get_input('description', '');
    $category_id = get_input('category_id', null);
    $artist_name = get_input('artist_name', get_logged_in_user()['username']);

    // Validate file
    $validation = validate_upload($_FILES['image']);
    if (!$validation['valid']) {
        echo json_encode(['success' => false, 'error' => $validation['error']]);
        return;
    }

    // Generate unique filename
    $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $filename = date('Ymd_') . random_string(8) . '.' . strtolower($extension);
    $target_path = UPLOAD_DIR . $filename;

    // Move uploaded file
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
        echo json_encode(['success' => false, 'error' => 'Failed to save uploaded file']);
        return;
    }

    chmod($target_path, 0644);

    // Create thumbnail
    create_thumbnail($target_path, THUMB_DIR . $filename, THUMB_WIDTH);

    // Save to database
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO images (user_id, category_id, artist_name, title, description, filename, original_name, mime_type, size) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            get_logged_in_user_id(),
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
        unlink($target_path);
        if (file_exists(THUMB_DIR . $filename)) {
            unlink(THUMB_DIR . $filename);
        }
        error_log("Database error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error occurred']);
    }
}
