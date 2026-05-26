<?php
/**
 * Copy this file to stripe.local.php and add your Stripe test keys.
 * Dashboard: https://dashboard.stripe.com/test/apikeys
 */
return [
    'publishable_key' => 'pk_test_YOUR_PUBLISHABLE_KEY',
    'secret_key' => 'sk_test_YOUR_SECRET_KEY',
    // Stripe test mode uses USD (PKR is not supported for card charges)
    'currency' => 'usd',
    // Approximate PKR → USD for test charges (adjust or remove in production)
    'pkr_per_usd' => 280,
];
