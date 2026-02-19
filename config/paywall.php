<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Paywall Webhook Configuration
    |--------------------------------------------------------------------------
    */

    // Admin chat ID for notifications
    'admin_chat_id' => (int) env('PAYWALL_ADMIN_CHAT_ID', 208791603),

    // Test user ID for webhook testing
    'test_user_id' => env('PAYWALL_TEST_USER_ID', '1234567890'),

    // Default plan tokens for new subscriptions
    'default_plan_tokens' => (int) env('PAYWALL_DEFAULT_PLAN_TOKENS', 1000000),

    // Webhook types
    'webhook_types' => [
        'update' => 'update',
        'cancel' => 'cancel',
    ],
];
