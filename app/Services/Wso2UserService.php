<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class Wso2UserService
{
    public function getUsersList()
    {
        $baseUrl = config('services.wso2.base_url');

        // 1. Token OAuth2 (Client Credentials)
        $tokenResponse = Http::asForm()
            ->withOptions(['verify' => false])
            ->withBasicAuth(config('services.wso2.client_id'), config('services.wso2.client_secret'))
            ->post($baseUrl . '/oauth2/token', [
                'grant_type' => 'client_credentials',
                'scope' => 'internal_user_mgt_list internal_user_mgt_view',
            ]);

        $accessToken = $tokenResponse->json()['access_token'] ?? null;

        if (!$accessToken) {
            return ['error' => 'Impossibile ottenere il token per gli utenti'];
        }

        // 2. Chiamata SCIM2 per la lista utenti
        $response = Http::withToken($accessToken)
            ->withOptions(['verify' => false])
            ->get($baseUrl . '/scim2/Users');

        if ($response->failed()) {
            return ['error' => 'Errore API SCIM2', 'details' => $response->json()];
        }

        $resources = $response->json()['Resources'] ?? [];

        // 3. Mappatura pulita dei dati
        return collect($resources)->map(function ($user) {
            // Estrazione email (gestisce array di oggetti o stringhe)
            $email = 'N/A';
            if (!empty($user['emails'])) {
                $firstEmail = $user['emails'][0];
                $email = is_array($firstEmail) ? ($firstEmail['value'] ?? 'N/A') : $firstEmail;
            }

            return [
                'id' => $user['id'],
                'username' => $user['userName'],
                'email' => $email,
            ];
        })->toArray();
    }
}
