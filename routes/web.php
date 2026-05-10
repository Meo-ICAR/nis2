<?php

use App\Http\Controllers\WSO2SyncController;
use App\Models\User;
use App\Services\WSO2SocialiteDebugService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;

// use Laravel\Socialite\Socialite;

Route::get('/', function () {
    $strategicApplications = \App\Models\Application::where('is_strategic', true)->get();
    return view('welcome', compact('strategicApplications'));
});

/*
 * Route::get('/', function () {
 *     return view('welcome');
 * });
 */

Route::get('/auth/redirect', function () {
    return Socialite::driver('oidc')->redirect();
});

Route::get('/auth/callback', function () {
    $debugService = new WSO2SocialiteDebugService();

    try {
        Log::info('WSO2 Callback: Starting user retrieval');

        $oidcUser = Socialite::driver('oidc')->stateless()->user();

        Log::info('WSO2 Callback: User retrieved successfully', [
            'user_id' => $oidcUser->getId(),
            'email' => $oidcUser->getEmail(),
            'name' => $oidcUser->getName(),
        ]);

        // Debug completo dell'utente ricevuto
        $debugService->logSocialiteDebug('callback');

        // $user = Socialite::driver('github')->userFromToken($token);

        /*
         * // OAuth 2.0 providers...
         * $token = $user->token;
         *   $refreshToken = $user->refreshToken;
         *   $expiresIn = $user->expiresIn;
         *
         *   // OAuth 1.0 providers...
         *   $token = $user->token;
         *   $tokenSecret = $user->tokenSecret;
         *
         *   // All providers...
         *   $user->getId();
         *   $user->getNickname();
         *   $user->getName();
         *   $user->getEmail();
         *   $user->getAvatar();
         */

        // Correggo il campo per usare 'sub' invece di 'oidc_id' come nel modello User
        $user = User::updateOrCreate([
            'sub' => $oidcUser->getId(),
        ], [
            'name' => $oidcUser->getName(),
            'email' => $oidcUser->getEmail(),
            // Rimuovo i campi oidc_token e oidc_refresh_token che non esistono nella migration
        ]);

        Log::info('WSO2 Callback: User created/updated', [
            'user_id' => $user->id,
            'email' => $user->email,
            'was_recently_created' => $user->wasRecentlyCreated,
        ]);

        Auth::login($user);

        Log::info('WSO2 Callback: User logged in successfully');

        return redirect('/dashboard');
    } catch (\Exception $e) {
        Log::error('WSO2 Callback: Error during authentication', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);

        // Debug anche in caso di errore
        try {
            $debugService->logSocialiteDebug('callback_error');
        } catch (\Exception $debugException) {
            Log::error('WSO2 Callback: Debug service failed', [
                'debug_error' => $debugException->getMessage(),
            ]);
        }

        // Reindirizza con errore
        return redirect('/login?error=auth_failed&message=' . urlencode($e->getMessage()));
    }
});

// WSO2 Sync API Routes (admin only)
Route::middleware(['auth'])->prefix('api/wso2')->group(function () {
    // Users sync
    Route::get('/stats', [WSO2SyncController::class, 'stats']);
    Route::post('/sync', [WSO2SyncController::class, 'sync']);

    // Applications sync
    Route::get('/applications/stats', [WSO2SyncController::class, 'applicationsStats']);
    Route::post('/applications/sync', [WSO2SyncController::class, 'syncApplications']);

    // General status
    Route::get('/status', [WSO2SyncController::class, 'status']);
});
