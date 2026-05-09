<?php

namespace App\Providers\Filament;

use DutchCodingCompany\FilamentSocialite\FilamentSocialitePlugin;
use DutchCodingCompany\FilamentSocialite\Provider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Support\Colors\Color;
use Filament\Support\Colors;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->plugin(
                /*
                 * ->createUserUsing(function (string $provider, $oauthUser, $plugin) {
                 *     // Logica personalizzata per creare l'utente
                 *     return User::create([
                 *         'name' => $oauthUser->getName(),
                 *         'email' => $oauthUser->getEmail(),
                 *         'password' => null,  // Password nullable obbligatoria per Socialite
                 *         'avatar_url' => $oauthUser->getAvatar(),  // Salva l'URL di Google
                 *         'email_verified_at' => now(),  // Google certifica l'email, quindi la segniamo come verificata
                 *         'password' => Hash::make(Str::random(32)),  // Password casuale sicura
                 *     ]);
                 * }),
                 */
                FilamentSocialitePlugin::make()
                    ->registration(true)  // Abilita la registrazione automatica per nuovi utenti
                    // (required) Add providers corresponding with providers in `config/services.php`.
                    ->resolveUserUsing(function (string $provider, SocialiteUserContract $oauthUser, Authenticatable $user) {
                        // Salva il token di WSO2 nella sessione di Laravel
                        session()->put('wso2_access_token', $oauthUser->token);

                        return $user;
                    })
                    ->providers([
                        // Create a provider 'gitlab' corresponding to the Socialite driver with the same name.
                        Provider::make('oidc')
                            ->label('WSO2')
                            ->icon('fab-openid')
                            //  ->color(Color::hex('#2f2a6b'))
                            ->outlined(false)
                            ->stateless(false)
                        //   ->scopes(['...'])
                        //   ->with(['...']),
                    ])
                // (optional) Override the panel slug to be used in the oauth routes. Defaults to the panel's configured path.
                //  ->slug('admin')
                // (optional) Enable/disable registration of new (socialite-) users.
                //  ->registration(true)
                // (optional) Enable/disable registration of new (socialite-) users using a callback.
                // In this example, a login flow can only continue if there exists a user (Authenticatable) already.
                //   ->registration(fn(string $provider, SocialiteUserContract $oauthUser, ?Authenticatable $user) => (bool) $user)
                // (optional) Change the associated model class.
                //   ->userModelClass(\App\Models\User::class)
                // (optional) Change the associated socialite class (see below).
                //   ->socialiteUserModelClass(\App\Models\SocialiteUser::class)
            )
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
