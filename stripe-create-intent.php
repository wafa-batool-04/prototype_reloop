<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/stripe.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Please log in.']);
    exit;
}

if (!stripe_is_configured()) {
    http_response_code(503);
    echo json_encode(['ok' => false, 'error' => 'Stripe is not configured on the server.']);
    exit;
}

$amount_pkr = isset($_POST['amount_pkr']) ? (float) $_POST['amount_pkr'] : 0;
if ($amount_pkr <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid order amount.']);
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$email = $_SESSION['user_email'] ?? '';

$result = stripe_create_payment_intent($amount_pkr, $user_id, $email);

if (!$result['ok']) {
    http_response_code(400);
    echo json_encode($result);
    exit;
}

$cfg = stripe_config();
echo json_encode([
    'ok' => true,
    'clientSecret' => $result['client_secret'],
    'paymentIntentId' => $result['payment_intent_id'],
    'publishableKey' => $cfg['publishable_key'],
    'currency' => $result['currency'],
    'stripeAmount' => $result['amount'],
]);
