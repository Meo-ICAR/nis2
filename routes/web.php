<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;

// use Laravel\Socialite\Socialite;

Route::redirect('/', '/admin');

/*
 * Route::get('/', function () {
 *     return view('welcome');
 * });
 */

Route::get('/auth/redirect', function () {
    return Socialite::driver('oidc')->redirect();
});

Route::get('/auth/callback', function () {
    $oidcUser = Socialite::driver('oidc')->user();

    // $user = Socialite::driver('github')->userFromToken($token);

    /*
     * // OAuth 2.0 providers...
     *   $token = $user->token;
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

    $user = User::updateOrCreate([
        'oidc_id' => $oidcUser->id,
    ], [
        'name' => $oidcUser->name,
        'email' => $oidcUser->email,
        'oidc_token' => $oidcUser->token,
        'oidc_refresh_token' => $oidcUser->refreshToken,
    ]);

    Auth::login($user);

    return redirect('/dashboard');
});
