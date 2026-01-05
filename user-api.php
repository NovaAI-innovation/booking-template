<?php
/**
 * User Authentication API
 * Handles user registration, login, logout, and session management
 */

require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$action = $_GET['action'] ?? '';
$pdo = getDbConnection();

/**
 * REGISTER endpoint
 * Creates a new user account
 */
if ($action === 'register') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'POST method required']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    // Validate CSRF token
    if (!validateCsrfToken($input['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }

    $username = trim($input['username'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
    $dateOfBirth = $input['date_of_birth'] ?? '';

    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($dateOfBirth)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }

    // Username validation (alphanumeric, underscore, hyphen, 3-50 characters)
    if (!preg_match('/^[a-zA-Z0-9_-]{3,50}$/', $username)) {
        echo json_encode(['success' => false, 'message' => 'Username must be 3-50 characters (letters, numbers, _, -)']);
        exit;
    }

    // Email validation
    if (!isValidEmail($email)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        exit;
    }

    // Password validation
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters']);
        exit;
    }

    // Date of birth validation
    if (!isValidDateOfBirth($dateOfBirth)) {
        echo json_encode(['success' => false, 'message' => 'Invalid date of birth or you must be at least 18 years old']);
        exit;
    }

    // Check if username or email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
        exit;
    }

    // Create user
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password_hash, date_of_birth)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$username, $email, $passwordHash, $dateOfBirth]);
        $userId = $pdo->lastInsertId();

        // Auto-login after registration
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;
        $_SESSION['last_activity'] = time();

        echo json_encode([
            'success' => true,
            'message' => 'Registration successful',
            'user' => [
                'id' => $userId,
                'username' => $username,
                'email' => $email,
                'date_of_birth' => $dateOfBirth
            ]
        ]);
    } catch (PDOException $e) {
        error_log('Registration error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
    }
    exit;
}

/**
 * LOGIN endpoint
 * Authenticates a user
 */
if ($action === 'login') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'POST method required']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    // Validate CSRF token
    if (!validateCsrfToken($input['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }

    $username = trim($input['username'] ?? '');
    $password = $input['password'] ?? '';

    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Username and password are required']);
        exit;
    }

    // Fetch user (allow login with username or email)
    $stmt = $pdo->prepare("
        SELECT id, username, email, password_hash, date_of_birth
        FROM users
        WHERE (username = ? OR email = ?) AND is_active = 1
    ");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
        exit;
    }

    // Update last login
    $stmt = $pdo->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$user['id']]);

    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['last_activity'] = time();

    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'date_of_birth' => $user['date_of_birth']
        ]
    ]);
    exit;
}

/**
 * LOGOUT endpoint
 * Destroys user session
 */
if ($action === 'logout') {
    session_unset();
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
    exit;
}

/**
 * CHECK-AUTH endpoint
 * Verifies if user is logged in and returns user data
 */
if ($action === 'check-auth') {
    $user = getCurrentUser();

    if ($user) {
        echo json_encode([
            'authenticated' => true,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'date_of_birth' => $user['date_of_birth'],
                'created_at' => $user['created_at'],
                'last_login' => $user['last_login']
            ]
        ]);
    } else {
        echo json_encode(['authenticated' => false]);
    }
    exit;
}

/**
 * CHECK-GALLERY-ACCESS endpoint
 * Checks if user has purchased gallery access
 */
if ($action === 'check-gallery-access') {
    $user = getCurrentUser();

    if (!$user) {
        echo json_encode([
            'hasAccess' => false,
            'reason' => 'not_logged_in',
            'message' => 'Please login to check gallery access'
        ]);
        exit;
    }

    // Check if user has purchased access
    $stmt = $pdo->prepare("
        SELECT id, purchased_at
        FROM gallery_purchases
        WHERE user_id = ? AND status = 'completed'
        LIMIT 1
    ");
    $stmt->execute([$user['id']]);
    $purchase = $stmt->fetch();

    if ($purchase) {
        echo json_encode([
            'hasAccess' => true,
            'reason' => 'purchased',
            'purchasedAt' => $purchase['purchased_at'],
            'message' => 'You have access to the gallery'
        ]);
    } else {
        echo json_encode([
            'hasAccess' => false,
            'reason' => 'not_purchased',
            'message' => 'Purchase required to access gallery',
            'price' => GALLERY_ACCESS_PRICE / 100, // Convert cents to dollars
            'currency' => GALLERY_ACCESS_CURRENCY
        ]);
    }
    exit;
}

/**
 * GET-PROFILE endpoint
 * Get current user's profile information
 */
if ($action === 'get-profile') {
    $user = getCurrentUser();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit;
    }

    // Get gallery access status
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as has_access
        FROM gallery_purchases
        WHERE user_id = ? AND status = 'completed'
    ");
    $stmt->execute([$user['id']]);
    $accessData = $stmt->fetch();

    // Get total tips sent
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as tip_count, SUM(amount) as total_tipped
        FROM tips
        WHERE user_id = ? AND status = 'completed'
    ");
    $stmt->execute([$user['id']]);
    $tipsData = $stmt->fetch();

    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'date_of_birth' => $user['date_of_birth'],
            'created_at' => $user['created_at'],
            'last_login' => $user['last_login'],
            'has_gallery_access' => $accessData['has_access'] > 0,
            'tips_sent' => $tipsData['tip_count'] ?? 0,
            'total_tipped' => ($tipsData['total_tipped'] ?? 0) / 100 // Convert to dollars
        ]
    ]);
    exit;
}

// Invalid action
echo json_encode(['success' => false, 'message' => 'Invalid action']);
