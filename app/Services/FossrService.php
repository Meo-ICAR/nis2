<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FossrService
{
    protected string $authToken;

    /**
     * Esegue l'autenticazione OAuth2 (Password Grant Flow)
     */
    public function authenticate(): bool
    {
        /*
         * $response = Http::asForm()->post(config('services.fossr.auth_url'), [
         *     'grant_type' => 'password',
         *     'username' => config('services.fossr.username'),
         *     'password' => config('services.fossr.password'),
         *     'client_id' => config('services.fossr.client_id'),
         *     'client_secret' => config('services.fossr.client_secret'),
         * ]);
         */

        // Cambia il corpo della richiesta nel metodo authenticate()
        $response = Http::asForm()
            ->withBasicAuth(config('services.fossr.client_id'), config('services.fossr.client_secret'))
            ->post(config('services.fossr.auth_url'), [
                'grant_type' => 'client_credentials',
                'scope' => 'openid',
            ]);

        if ($response->successful()) {
            $this->authToken = $response->json('access_token');
            return true;
        }

        Log::error('FOSSR Auth Failed', $response->json() ?? []);
        return false;
    }

    /**
     * Recupera la lista dei progetti
     */
    public function getProgetti()
    {
        if (empty($this->authToken)) {
            $this->authenticate();
        }

        $response = Http::withToken($this->authToken)
            ->get(config('services.fossr.gateway_url') . '/progetti');

        return $response->successful() ? $response->json() : null;
    }
}
