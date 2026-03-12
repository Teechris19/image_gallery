<?php
/**
 * Authentication functions
 * User login, signup, session management
 */

// Define APP_ROOT if not already defined
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__, 2));
}

require_once APP_ROOT . '/application/core/config.php';
require_once APP_ROOT . '/application/core/database.php';
require_once APP_ROOT . '/application/functions/general.php';

/**
 * Register a new user
 * @param string $username
 * @param string $email
 * @param string $password
 * @return array ['success' => bool, 'user_id' => int|null, 'error' => string]
 */
function register_user($username, $email, $password) {
    // Validate input
    if (empty($username) || empty($email) || empty($password)) {
        return ['success' => false, 'user_id' => null, 'error' => 'All fields are required'];
    }

    if (strlen($username) < 3 || strlen($username) > 50) {
        return ['success' => false, 'user_id' => null, 'error' => 'Username must be between 3 and 50 characters'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'user_id' => null, 'error' => 'Invalid email address'];
    }

    if (strlen($password) < 6) {
        return ['success' => false, 'user_id' => null, 'error' => 'Password must be at least 6 characters'];
    }

    try {
        $pdo = getConnection();

        // Check if username exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            return ['success' => false, 'user_id' => null, 'error' => 'Username already taken'];
        }

        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'user_id' => null, 'error' => 'Email already registered'];
        }

        // Hash password and insert user
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $hashed_password]);

        return ['success' => true, 'user_id' => $pdo->lastInsertId(), 'error' => ''];
    } catch (PDOException $e) {
        error_log("Registration error: " . $e->getMessage());
        return ['success' => false, 'user_id' => null, 'error' => 'Registration failed. Please try again.'];
    }
}

/**
 * Authenticate user login
 * @param string $username Email or username
 * @param string $password
 * @return array ['success' => bool, 'user' => array|null, 'error' => string]
 */
function login_user($username, $password) {
    if (empty($username) || empty($password)) {
        return ['success' => false, 'user' => null, 'error' => 'Username and password are required'];
    }

    try {
        $pdo = getConnection();

        // Find user by username or email
        $stmt = $pdo->prepare("SELECT id, username, email, password, avatar, bio, location, portfolio_url FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'user' => null, 'error' => 'Invalid credentials'];
        }

        if (!password_verify($password, $user['password'])) {
            // Check if password matches plain text (for legacy accounts)
            if ($user['password'] !== $password) {
                return ['success' => false, 'user' => null, 'error' => 'Invalid credentials'];
            }
        }

        // If password is plain text, hash it for future logins
        if (strlen($user['password']) < 60) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $user['id']]);
            $user['password'] = $hashed;
        }

        // Remove password from user data
        unset($user['password']);

        return ['success' => true, 'user' => $user, 'error' => ''];
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        return ['success' => false, 'user' => null, 'error' => 'Login failed. Please try again.'];
    }
}

/**
 * Start user session
 * @param array $user User data
 */
function start_user_session($user) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['user'] = $user;
    $_SESSION['user_id'] = $user['id'];
}

/**
 * End user session (logout)
 */
function logout_user() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    unset($_SESSION['user']);
    unset($_SESSION['user_id']);
}

/**
 * Check if user is logged in
 * @return bool
 */
function is_logged_in() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user']) && isset($_SESSION['user_id']);
}

/**
 * Get current logged-in user
 * @return array|null
 */
function get_logged_in_user() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return $_SESSION['user'] ?? null;
}

/**
 * Get current user ID
 * @return int|null
 */
function get_logged_in_user_id() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return $_SESSION['user_id'] ?? null;
}

/**
 * Update user profile
 * @param int $user_id
 * @param array $data ['bio', 'location', 'portfolio_url']
 * @return array ['success' => bool, 'error' => string]
 */
function update_user_profile($user_id, $data) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("UPDATE users SET bio = ?, location = ?, portfolio_url = ? WHERE id = ?");
        $stmt->execute([
            $data['bio'] ?? null,
            $data['location'] ?? null,
            $data['portfolio_url'] ?? null,
            $user_id
        ]);
        return ['success' => true, 'error' => ''];
    } catch (PDOException $e) {
        error_log("Profile update error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to update profile'];
    }
}

/**
 * Get user by ID
 * @param int $id
 * @return array|null
 */
function get_user_by_id($id) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT id, username, avatar, bio, location, portfolio_url, created_at FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    } catch (PDOException $e) {
        error_log("Get user error: " . $e->getMessage());
        return null;
    }
}

/**
 * Get user by username
 * @param string $username
 * @return array|null
 */
function get_user_by_username($username) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT id, username, avatar, bio, location, portfolio_url, created_at FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch() ?: null;
    } catch (PDOException $e) {
        error_log("Get user error: " . $e->getMessage());
        return null;
    }
}

/**
 * Get public user info by username (excludes email for privacy)
 * @param string $username
 * @return array|null
 */
function get_public_user_by_username($username) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT id, username, avatar, bio, location, portfolio_url, created_at FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch() ?: null;
    } catch (PDOException $e) {
        error_log("Get public user error: " . $e->getMessage());
        return null;
    }
}

/**
 * Get public user info by ID (excludes email for privacy)
 * @param int $id
 * @return array|null
 */
function get_public_user_by_id($id) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT id, username, avatar, bio, location, portfolio_url, created_at FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    } catch (PDOException $e) {
        error_log("Get public user error: " . $e->getMessage());
        return null;
    }
}

/**
 * Get user statistics
 * @param int $user_id
 * @return array ['artworks' => int, 'followers' => int, 'following' => int]
 */
function get_user_stats($user_id) {
    try {
        $pdo = getConnection();

        // Get artworks count
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM images WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $artworks = $stmt->fetch()['count'];

        // Get followers count
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM follows WHERE following_id = ?");
        $stmt->execute([$user_id]);
        $followers = $stmt->fetch()['count'];

        // Get following count
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM follows WHERE follower_id = ?");
        $stmt->execute([$user_id]);
        $following = $stmt->fetch()['count'];

        return [
            'artworks' => $artworks,
            'followers' => $followers,
            'following' => $following
        ];
    } catch (PDOException $e) {
        error_log("Get stats error: " . $e->getMessage());
        return ['artworks' => 0, 'followers' => 0, 'following' => 0];
    }
}

/**
 * Check if current user is following another user
 * @param int $follower_id
 * @param int $following_id
 * @return bool
 */
function is_following($follower_id, $following_id) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT id FROM follows WHERE follower_id = ? AND following_id = ?");
        $stmt->execute([$follower_id, $following_id]);
        return (bool) $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Check follow error: " . $e->getMessage());
        return false;
    }
}

/**
 * Follow a user
 * @param int $follower_id
 * @param int $following_id
 * @return array ['success' => bool, 'error' => string]
 */
function follow_user($follower_id, $following_id) {
    if ($follower_id === $following_id) {
        return ['success' => false, 'error' => 'You cannot follow yourself'];
    }

    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("INSERT INTO follows (follower_id, following_id) VALUES (?, ?)");
        $stmt->execute([$follower_id, $following_id]);
        return ['success' => true, 'error' => ''];
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Duplicate entry
            return ['success' => false, 'error' => 'Already following this user'];
        }
        error_log("Follow error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to follow user'];
    }
}

/**
 * Unfollow a user
 * @param int $follower_id
 * @param int $following_id
 * @return array ['success' => bool, 'error' => string]
 */
function unfollow_user($follower_id, $following_id) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?");
        $stmt->execute([$follower_id, $following_id]);
        return ['success' => true, 'error' => ''];
    } catch (PDOException $e) {
        error_log("Unfollow error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to unfollow user'];
    }
}
