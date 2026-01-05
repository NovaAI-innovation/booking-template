<?php
/**
 * Payment API
 * Handles Stripe Checkout sessions for gallery access and tips
 */

require_once 'config.php';
require_once 'vendor/autoload.php';

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

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
 * CREATE GALLERY CHECKOUT SESSION
 * Creates a Stripe Checkout session for gallery access purchase
 */
if ($action === 'create-gallery-checkout') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'POST method required']);
        exit;
    }

    $user = getCurrentUser();
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Must be logged in to purchase']);
        exit;
    }

    // Check if already purchased
    $stmt = $pdo->prepare("SELECT id FROM gallery_purchases WHERE user_id = ? AND status = 'completed'");
    $stmt->execute([$user['id']]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'You already have gallery access']);
        exit;
    }

    try {
        // Get protocol (http or https)
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';

        $checkoutSession = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => GALLERY_ACCESS_CURRENCY,
                    'product_data' => [
                        'name' => GALLERY_ACCESS_PRODUCT_NAME,
                        'description' => GALLERY_ACCESS_DESCRIPTION,
                    ],
                    'unit_amount' => GALLERY_ACCESS_PRICE,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $protocol . '://' . $host . '/gallery.html?purchase=success',
            'cancel_url' => $protocol . '://' . $host . '/gallery.html?purchase=cancelled',
            'client_reference_id' => (string)$user['id'],
            'metadata' => [
                'user_id' => (string)$user['id'],
                'username' => $user['username'],
                'type' => 'gallery_access',
            ],
        ]);

        echo json_encode([
            'success' => true,
            'sessionId' => $checkoutSession->id,
            'url' => $checkoutSession->url
        ]);
    } catch (\Stripe\Exception\ApiErrorException $e) {
        error_log('Stripe API error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Payment system error. Please try again.']);
    } catch (Exception $e) {
        error_log('Gallery checkout error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
    }
    exit;
}

/**
 * CREATE TIP CHECKOUT SESSION
 * Creates a Stripe Checkout session for sending a tip
 */
if ($action === 'create-tip-checkout') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'POST method required']);
        exit;
    }

    $user = getCurrentUser();
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Must be logged in to send tips']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $amount = intval($input['amount'] ?? 0); // in cents
    $message = trim($input['message'] ?? '');

    // Validate amount
    if ($amount < TIP_MIN_AMOUNT) {
        echo json_encode(['success' => false, 'message' => 'Minimum tip amount is $' . (TIP_MIN_AMOUNT / 100)]);
        exit;
    }

    if ($amount > TIP_MAX_AMOUNT) {
        echo json_encode(['success' => false, 'message' => 'Maximum tip amount is $' . (TIP_MAX_AMOUNT / 100)]);
        exit;
    }

    // Sanitize message
    if (strlen($message) > 500) {
        $message = substr($message, 0, 500);
    }

    try {
        // Get protocol (http or https)
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';

        $checkoutSession = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => 'Tip',
                        'description' => $message ?: 'Thank you for your support!',
                    ],
                    'unit_amount' => $amount,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $protocol . '://' . $host . '/index.html?tip=success',
            'cancel_url' => $protocol . '://' . $host . '/index.html?tip=cancelled',
            'client_reference_id' => (string)$user['id'],
            'metadata' => [
                'user_id' => (string)$user['id'],
                'username' => $user['username'],
                'type' => 'tip',
                'message' => $message,
            ],
        ]);

        echo json_encode([
            'success' => true,
            'sessionId' => $checkoutSession->id,
            'url' => $checkoutSession->url
        ]);
    } catch (\Stripe\Exception\ApiErrorException $e) {
        error_log('Stripe API error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Payment system error. Please try again.']);
    } catch (Exception $e) {
        error_log('Tip checkout error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
    }
    exit;
}

/**
 * GET RECENT TIPPERS
 * Returns the 10 most recent tippers with username, amount, DOB, date, and time
 */
if ($action === 'get-recent-tippers') {
    $limit = min(intval($_GET['limit'] ?? 10), 50); // Max 50, default 10

    try {
        $stmt = $pdo->prepare("
            SELECT
                u.username,
                u.date_of_birth,
                t.amount,
                t.created_at
            FROM tips t
            JOIN users u ON t.user_id = u.id
            WHERE t.status = 'completed' AND t.is_anonymous = 0
            ORDER BY t.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        $tippers = $stmt->fetchAll();

        // Format data
        $formattedTippers = array_map(function($tip) {
            $datetime = new DateTime($tip['created_at']);
            return [
                'username' => sanitizeOutput($tip['username']),
                'amount' => number_format($tip['amount'] / 100, 2), // Convert cents to dollars
                'dob' => $tip['date_of_birth'], // User's date of birth
                'date' => $datetime->format('Y-m-d'), // Date of donation
                'time' => $datetime->format('H:i:s'), // Time of donation
                'datetime' => $tip['created_at'],
            ];
        }, $tippers);

        echo json_encode(['success' => true, 'tippers' => $formattedTippers]);
    } catch (PDOException $e) {
        error_log('Get tippers error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to fetch tippers']);
    }
    exit;
}

/**
 * GET TOTAL TIPS STATS
 * Returns total tips received and count
 */
if ($action === 'get-tips-stats') {
    try {
        $stmt = $pdo->prepare("
            SELECT
                COUNT(*) as tip_count,
                SUM(amount) as total_amount
            FROM tips
            WHERE status = 'completed'
        ");
        $stmt->execute();
        $stats = $stmt->fetch();

        echo json_encode([
            'success' => true,
            'total_tips' => $stats['tip_count'] ?? 0,
            'total_amount' => number_format(($stats['total_amount'] ?? 0) / 100, 2)
        ]);
    } catch (PDOException $e) {
        error_log('Get tips stats error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to fetch stats']);
    }
    exit;
}

// Invalid action
echo json_encode(['success' => false, 'message' => 'Invalid action']);
