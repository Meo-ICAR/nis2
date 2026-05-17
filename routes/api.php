<?php

use App\Http\Controllers\Api\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/webhooks/{token}', [WebhookController::class, 'handle'])
        ->name('api.v1.webhooks');

    // Togliamo il v1 da qui dentro, ci pensa già Laravel a inserirlo!
    Route::get('/test-monitoring', function (Request $request) {
        usleep(rand(50000, 300000));

        return response()->json([
            'status' => 'success',
            'message' => 'FOSSR Monitoring Dummy API works!',
            'timestamp' => now()->toIso8601String(),
            'environment' => 'external_server'
        ], 200);
    });
});
