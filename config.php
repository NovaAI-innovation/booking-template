<?php
/**
 * Central Configuration File
 * Database, Stripe, and Security Settings
 */

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Database configuration
define('DB_PATH', __DIR__ . '/database.sqlite');

// Stripe configuration (TEST MODE - replace with live keys in production)
define('STRIPE_SECRET_KEY', 'sk_test_YOUR_SECRET_KEY_HERE'); // Replace with your key
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_YOUR_PUBLISHABLE_KEY_HERE'); // Replace with your key
define('STRIPE_WEBHOOK_SECRET', 'whsec_YOUR_WEBHOOK_SECRET_HERE'); // Replace after setting up webhook

// Gallery access pricing
define('GALLERY_ACCESS_PRICE', 1999); // $19.99 in cents
define('GALLERY_ACCESS_CURRENCY', 'usd');
define('GALLERY_ACCESS_PRODUCT_NAME', 'Gallery Access - Lifetime');
define('GALLERY_ACCESS_DESCRIPTION', 'One-time purchase for permanent gallery access');

// Tipping configuration
define('TIP_MIN_AMOUNT', 100); // $1.00 minimum in cents
define('TIP_MAX_AMOUNT', 100000); // $1,000 maximum in cents

// Security settings
define('USER_SESSION_TIMEOUT', 86400); // 24 hours in seconds
define('PASSWORD_MIN_LENGTH', 8);
define('SESSION_TOKEN_LENGTH', 32);

// Session configuration for users
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
ini_set('session.cookie_samesite', 'Lax');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Get database connection (PDO SQLite)
 * @return PDO Database connection object
 */
function getDbConnection() {
    try {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        // Enable foreign keys
        $pdo->exec('PRAGMA foreign_keys = ON;');
        return $pdo;
    } catch (PDOException $e) {
        error_log('Database connection failed: ' . $e->getMessage());
        http_response_code(500);
        die(json_encode(['success' => false, 'message' => 'Database connection failed']));
    }
}

/**
 * Generate CSRF token for forms
 * @return string CSRF token
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 * @param string $token Token to validate
 * @return bool True if valid
 */
function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get current logged-in user from session
 * @return array|null User data or null if not logged in
 */
function getCurrentUser() {
    $pdo = getDbConnection();

    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    // Check session timeout
    if (isset($_SESSION['last_activity'])) {
        $inactiveTime = time() - $_SESSION['last_activity'];
        if ($inactiveTime > USER_SESSION_TIMEOUT) {
            // Session expired
            session_unset();
            session_destroy();
            return null;
        }
    }

    // Update last activity time
    $_SESSION['last_activity'] = time();

    $stmt = $pdo->prepare("
        SELECT id, username, email, date_of_birth, created_at, last_login
        FROM users
        WHERE id = ? AND is_active = 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * Check if user is logged in
 * @return bool True if logged in
 */
function isUserLoggedIn() {
    return getCurrentUser() !== null;
}

/**
 * Require user to be logged in (redirect if not)
 * @param string $redirectUrl URL to redirect to if not logged in
 */
function requireUserLogin($redirectUrl = '/gallery.html') {
    if (!isUserLoggedIn()) {
        http_response_code(401);
        die(json_encode(['success' => false, 'message' => 'Authentication required', 'redirect' => $redirectUrl]));
    }
}

/**
 * Sanitize output for HTML display
 * @param string $text Text to sanitize
 * @return string Sanitized text
 */
function sanitizeOutput($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email address
 * @param string $email Email to validate
 * @return bool True if valid
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate date of birth (must be valid date and user must be 18+)
 * @param string $dob Date of birth (YYYY-MM-DD)
 * @return bool True if valid
 */
function isValidDateOfBirth($dob) {
    $date = DateTime::createFromFormat('Y-m-d', $dob);
    if (!$date || $date->format('Y-m-d') !== $dob) {
        return false;
    }

    // Check if user is at least 18 years old
    $now = new DateTime();
    $age = $now->diff($date)->y;
    return $age >= 18;
}

/**
 * Format date for display
 * @param string $datetime Datetime string
 * @param string $format Format string
 * @return string Formatted date
 */
function formatDate($datetime, $format = 'Y-m-d H:i:s') {
    $date = new DateTime($datetime);
    return $date->format($format);
}

/**
 * Get CSRF token for frontend
 * This endpoint can be called via GET to get a token
 */
if (isset($_GET['get_csrf_token'])) {
    header('Content-Type: application/json');
    echo json_encode(['csrf_token' => generateCsrfToken()]);
    exit;
}
