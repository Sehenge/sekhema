<?php

return [
    'sekhema' => [
        'ask_subscription_text' => env('ASK_SUBSCRIPTION_TEXT', ''),
        'new_subscription_text' => env('NEW_SUBSCRIPTION_TEXT', ''),
        'cancel_subscription_text' => env('CANCEL_SUBSCRIPTION_TEXT', ''),
        'buy_button_link' => env('BUY_BUTTON_LINK', ''),
        'buy_button_text' => env('BUY_BUTTON_TEXT', ''),
        'highload_text' => env('HIGHLOAD_TEXT', ''),
    ],
    'openai' => [
        'access_key' => env('OPENAI_ACCESS_TOKEN', ''),
        'bot_id' => env('OPENAI_BOT_ID', ''),
        'workspace_id' => env('OPENAI_WORKSPACE_ID', ''),
    ],
];
