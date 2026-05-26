<?php
/**
 * Stripe helpers (REST API via cURL — no Composer required).
 */

function stripe_config(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $defaults = [
        'publishable_key' => '',
        'secret_key' => '',
        'currency' => 'usd',
        'pkr_per_usd' => 280,
    ];

    $local = __DIR__ . '/stripe.local.php';
    if (is_file($local)) {
        $loaded = require $local;
        if (is_array($loaded)) {
            $config = array_merge($defaults, $loaded);
            return $config;
        }
    }

    $config = $defaults;
    return $config;
}

function stripe_is_configured(): bool
{
    $cfg = stripe_config();
    return !empty($cfg['secret_key'])
        && !empty($cfg['publishable_key'])
        && strpos($cfg['secret_key'], 'sk_') === 0
        && strpos($cfg['publishable_key'], 'pk_') === 0;
}

/** Convert PKR cart total to Stripe amount in smallest currency unit (e.g. cents). */
function stripe_amount_from_pkr(float $amount_pkr): int
{
    $cfg = stripe_config();
    $rate = max(1, (float) ($cfg['pkr_per_usd'] ?? 280));
    $usd = $amount_pkr / $rate;
    return max(50, (int) round($usd * 100));
}

function stripe_api_request(string $method, string $path, array $params = []): array
{
    $cfg = stripe_config();
    $url = 'https://api.stripe.com/v1/' . ltrim($path, '/');

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_USERPWD => $cfg['secret_key'] . ':',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
    ]);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    } elseif ($method === 'GET') {
        if ($params) {
            $url .= '?' . http_build_query($params);
            curl_setopt($ch, CURLOPT_URL, $url);
        }
    }

    $body = curl_exec($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($errno) {
        return ['ok' => false, 'error' => 'Stripe connection failed: ' . $error];
    }

    $data = json_decode($body, true);
    if (!is_array($data)) {
        return ['ok' => false, 'error' => 'Invalid response from Stripe'];
    }

    if (isset($data['error'])) {
        $msg = $data['error']['message'] ?? 'Stripe API error';
        return ['ok' => false, 'error' => $msg];
    }

    return ['ok' => true, 'data' => $data];
}

function stripe_create_payment_intent(float $amount_pkr, int $user_id, string $email = ''): array
{
    if (!stripe_is_configured()) {
        return ['ok' => false, 'error' => 'Stripe is not configured. Copy config/stripe.example.php to config/stripe.local.php and add your test keys.'];
    }

    $cfg = stripe_config();
    $amount = stripe_amount_from_pkr($amount_pkr);

    $params = [
        'amount' => $amount,
        'currency' => $cfg['currency'],
        'automatic_payment_methods[enabled]' => 'true',
        'metadata[user_id]' => (string) $user_id,
        'metadata[amount_pkr]' => (string) $amount_pkr,
    ];

    if ($email !== '') {
        $params['receipt_email'] = $email;
    }

    $result = stripe_api_request('POST', 'payment_intents', $params);
    if (!$result['ok']) {
        return $result;
    }

    return [
        'ok' => true,
        'client_secret' => $result['data']['client_secret'],
        'payment_intent_id' => $result['data']['id'],
        'amount' => $amount,
        'currency' => $cfg['currency'],
    ];
}

function stripe_verify_payment_intent(string $payment_intent_id, float $expected_pkr): array
{
    if (!stripe_is_configured()) {
        return ['ok' => false, 'error' => 'Stripe is not configured.'];
    }

    if ($payment_intent_id === '' || strpos($payment_intent_id, 'pi_') !== 0) {
        return ['ok' => false, 'error' => 'Invalid payment reference.'];
    }

    $result = stripe_api_request('GET', 'payment_intents/' . urlencode($payment_intent_id));
    if (!$result['ok']) {
        return $result;
    }

    $intent = $result['data'];
    if (($intent['status'] ?? '') !== 'succeeded') {
        return ['ok' => false, 'error' => 'Payment was not completed. Status: ' . ($intent['status'] ?? 'unknown')];
    }

    $expected_amount = stripe_amount_from_pkr($expected_pkr);
    if ((int) ($intent['amount'] ?? 0) !== $expected_amount) {
        return ['ok' => false, 'error' => 'Payment amount does not match order total.'];
    }

    $cfg = stripe_config();
    if (($intent['currency'] ?? '') !== $cfg['currency']) {
        return ['ok' => false, 'error' => 'Payment currency mismatch.'];
    }

    return ['ok' => true, 'intent' => $intent];
}
