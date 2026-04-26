<?php
// config/paymongo.php
// ============================================================
//  PayMongo API Keys Configuration
//  Get your keys from: https://dashboard.paymongo.com/developers
//
//  TEST keys  → sk_test_xxx / pk_test_xxx  (no real charges)
//  LIVE keys  → sk_live_xxx / pk_live_xxx  (real charges)
//
//  Replace the placeholder values below with your actual keys.
// ============================================================

define('PAYMONGO_SECRET_KEY', 'sk_test_YOUR_SECRET_KEY_HERE');
define('PAYMONGO_PUBLIC_KEY', 'pk_test_YOUR_PUBLIC_KEY_HERE');

// Detected automatically from the key prefix – do not change.
define('PAYMONGO_MODE', str_starts_with(PAYMONGO_SECRET_KEY, 'sk_live_') ? 'live' : 'test');

// Set to true once you replace the placeholder keys above.
define('PAYMONGO_CONFIGURED', !str_contains(PAYMONGO_SECRET_KEY, 'YOUR_SECRET_KEY_HERE'));
