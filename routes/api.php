<?php

declare(strict_types=1);

use App\Http\Controllers\TelegramWebhooks;
use App\Http\Controllers\PaywallWebhooks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', static function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/telegram/webhook', [TelegramWebhooks::class, 'handle']);
Route::get('/telegram/setWebhook', [TelegramWebhooks::class, 'setWebhook']);

Route::post('/paywall/webhook', [PaywallWebhooks::class, 'paywallWebhook']);
