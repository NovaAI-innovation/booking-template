<?php
/**
 * Stripe Webhook Handler
 * Processes Stripe webhook events for payment confirmation
 *
 * Set this URL as your webhook endpoint in Stripe Dashboard:
 * https://yourdomain.com/stripe-webhook.php
 *
 * Events to listen for:
 * - checkout.session.completed
 */

require_once 'config.php';
require_once 'vendor/autoload.php';

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

// Get the webhook payload
$payload = @file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

// Log webhook attempt
error_log('Stripe webhook received: ' . substr($payload, 0, 200));

try {
    // Verify webhook signature
    $event = \Stripe\Webhook::constructEvent(
        $payload,
        $sigHeader,
        STRIPE_WEBHOOK_SECRET
    );
} catch (\UnexpectedValueException $e) {
    // Invalid payload
    error_log('Webhook error - invalid payload: ' . $e->getMessage());
    http_response_code(400);
    exit;
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    // Invalid signature
    error_log('Webhook error - invalid signature: ' . $e->getMessage());
    http_response_code(400);
    exit;
}

$pdo = getDbConnection();

// Check if event already processed (idempotency)
try {
    $stmt = $pdo->prepare("SELECT id FROM stripe_webhook_events WHERE stripe_event_id = ?");
    $stmt->execute([$event->id]);
    if ($stmt->fetch()) {
        // Event already processed
        error_log('Webhook event already processed: ' . $event->id);
        http_response_code(200);
        exit;
    }
} catch (PDOException $e) {
    error_log('Webhook error checking idempotency: ' . $e->getMessage());
}

// Handle the event
if ($event->type === 'checkout.session.completed') {
    $session = $event->data->object;

    error_log('Processing checkout session: ' . $session->id);
    error_log('Session metadata: ' . json_encode($session->metadata));

    $userId = $session->metadata->user_id ?? null;
    $type = $session->metadata->type ?? null;
    $paymentIntentId = $session->payment_intent ?? null;

    if (!$userId || !$type || !$paymentIntentId) {
        error_log('Webhook error - missing metadata: userId=' . $userId . ', type=' . $type . ', paymentIntent=' . $paymentIntentId);
        http_response_code(400);
        exit;
    }

    try {
        $pdo->beginTransaction();

        if ($type === 'gallery_access') {
            // Record gallery purchase
            error_log('Recording gallery purchase for user: ' . $userId);

            $stmt = $pdo->prepare("
                INSERT INTO gallery_purchases
                (user_id, stripe_payment_intent_id, stripe_checkout_session_id, amount_paid, currency, status)
                VALUES (?, ?, ?, ?, ?, 'completed')
            ");
            $stmt->execute([
                $userId,
                $paymentIntentId,
                $session->id,
                $session->amount_total,
                $session->currency
            ]);

            error_log('Gallery purchase recorded: purchase_id=' . $pdo->lastInsertId());

        } elseif ($type === 'tip') {
            // Record tip
            error_log('Recording tip for user: ' . $userId);

            $message = $session->metadata->message ?? '';
            $stmt = $pdo->prepare("
                INSERT INTO tips
                (user_id, stripe_payment_intent_id, stripe_checkout_session_id, amount, currency, message, status)
                VALUES (?, ?, ?, ?, ?, ?, 'completed')
            ");
            $stmt->execute([
                $userId,
                $paymentIntentId,
                $session->id,
                $session->amount_total,
                $session->currency,
                $message
            ]);

            error_log('Tip recorded: tip_id=' . $pdo->lastInsertId());
        } else {
            error_log('Unknown payment type: ' . $type);
            $pdo->rollBack();
            http_response_code(400);
            exit;
        }

        // Log webhook event (idempotency)
        $stmt = $pdo->prepare("
            INSERT INTO stripe_webhook_events (stripe_event_id, event_type, payload)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$event->id, $event->type, $payload]);

        $pdo->commit();
        error_log('Webhook processed successfully: ' . $event->id);

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('Webhook database error: ' . $e->getMessage());
        http_response_code(500);
        exit;
    }
}

// Handle other event types if needed
elseif ($event->type === 'payment_intent.succeeded') {
    error_log('Payment intent succeeded: ' . $event->data->object->id);
    // Additional handling if needed
}

elseif ($event->type === 'payment_intent.payment_failed') {
    error_log('Payment intent failed: ' . $event->data->object->id);
    // Handle failed payment if needed
}

// Return 200 to acknowledge receipt
http_response_code(200);
exit;
