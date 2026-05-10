<?php

use App\Http\Controllers\Api\WebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/webhooks/{token}', [WebhookController::class, 'handle'])
        ->name('api.v1.webhooks');
});
