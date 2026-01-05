<?php
// Admin Configuration
// IMPORTANT: Change these credentials before deploying to production!

define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD_HASH', password_hash('admin123', PASSWORD_DEFAULT));

// Security settings
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_FILE_SIZE', 100 * 1024 * 1024); // 100MB

// Comprehensive image format support
define('ALLOWED_IMAGE_TYPES', [
    'image/jpeg',           // .jpg, .jpeg
    'image/png',            // .png
    'image/webp',           // .webp
    'image/gif',            // .gif
    'image/bmp',            // .bmp
    'image/x-ms-bmp',       // .bmp (alternative)
    'image/tiff',           // .tif, .tiff
    'image/svg+xml',        // .svg
    'image/x-icon',         // .ico
    'image/vnd.microsoft.icon', // .ico (alternative)
    'image/heic',           // .heic (iOS)
    'image/heif',           // .heif (iOS)
    'image/avif',           // .avif
    'image/apng'            // .apng
]);

// Comprehensive video format support
define('ALLOWED_VIDEO_TYPES', [
    'video/mp4',            // .mp4
    'video/webm',           // .webm
    'video/quicktime',      // .mov
    'video/x-msvideo',      // .avi
    'video/x-ms-wmv',       // .wmv
    'video/x-flv',          // .flv
    'video/mpeg',           // .mpeg, .mpg
    'video/ogg',            // .ogv
    'video/3gpp',           // .3gp
    'video/3gpp2',          // .3g2
    'video/x-matroska',     // .mkv
    'video/x-m4v',          // .m4v
    'application/x-mpegURL', // .m3u8 (HLS)
    'video/MP2T'            // .ts (MPEG transport stream)
]);

// Paths
define('GALLERY_DATA_FILE', __DIR__ . '/gallery-data.json');
define('UPLOAD_DIR_IMAGES', __DIR__ . '/Pics for website/');
define('UPLOAD_DIR_VIDEOS', __DIR__ . '/Videos for website/');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper function to check if user is logged in
function isLoggedIn() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        return false;
    }

    // Check session timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        return false;
    }

    $_SESSION['last_activity'] = time();
    return true;
}

// Helper function to require login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: admin-login.php');
        exit;
    }
}
?>
