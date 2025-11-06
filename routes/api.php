<?php

declare(strict_types=1);

use App\Http\Controllers\TelegramWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle']);
Route::get('/telegram/setWebhook', [TelegramWebhookController::class, 'setWebhook']);
