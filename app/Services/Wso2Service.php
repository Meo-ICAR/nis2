<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Wso2Service
{
    public function getUsersList()
    {
        $baseUrl = config('services.wso2.base_url');
        $clientId = config('services.wso2.client_id');
        $clientSecret = config('services.wso2.client_secret');

        // 1. Richiesta Token con entrambi gli scope (List e View)
        $tokenResponse = Http::asForm()
            ->withOptions(['verify' => false])
            ->withBasicAuth($clientId, $clientSecret)
            ->post($baseUrl . '/oauth2/token', [
                'grant_type' => 'client_credentials',
                'scope' => 'internal_user_mgt_list internal_user_mgt_view',
            ]);

        if ($tokenResponse->failed()) {
            dd('ERRORE TOKEN', $tokenResponse->json() ?: $tokenResponse->body());
        }

        $accessToken = $tokenResponse->json()['access_token'];

        // 2. Recupera Utenti
        $usersResponse = Http::withToken($accessToken)
            ->withOptions(['verify' => false])
            ->get($baseUrl . '/scim2/Users');

        if ($usersResponse->failed()) {
            dd('ERRORE SCIM', $usersResponse->json() ?: $usersResponse->body());
        }

        return $usersResponse->json();
    }

    public function getApplicationsList()
    {
        // Richiediamo il token con lo scope delle applicazioni
        $token = $this->getAccessToken('internal_application_mgt_view');

        if (!$token)
            return ['error' => 'Token fallito'];

        // L'endpoint delle applicazioni in WSO2 è solitamente questo:
        $appUrl = config('services.wso2.base_url') . '/api/server/v1/applications';

        $response = Http::withToken($token)
            ->withOptions(['verify' => false])
            ->get($appUrl);

        if ($response->failed()) {
            return [
                'error' => 'Chiamata API fallita',
                'details' => $response->json() ?: $response->body()
            ];
        }

        return $response->json();
    }
}
