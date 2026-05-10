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

        // Debug: Verifica che le variabili siano caricate
        if (!$baseUrl || !$clientId || !$clientSecret) {
            dd('ERRORE CONFIGURAZIONE: Controlla il file .env', [
                'base_url' => $baseUrl,
                'client_id' => $clientId,
                'secret_presente' => !empty($clientSecret)
            ]);
        }

        // 1. Richiesta Token
        $tokenResponse = Http::asForm()
            ->withOptions(['verify' => false])  // Ignora certificati SSL non validi localmente
            ->withBasicAuth($clientId, $clientSecret)
            ->post($baseUrl . '/oauth2/token', [
                'grant_type' => 'client_credentials',
                'scope' => 'internal_user_mgt_list',
            ]);

        if ($tokenResponse->failed()) {
            // DEBUG ATTIVO: Se il token fallisce, interrompe l'esecuzione e mostra il motivo
            dd('WSO2 TOKEN FAILED', [
                'Status' => $tokenResponse->status(),
                'Response' => $tokenResponse->json() ?? $tokenResponse->body(),
                'URL' => $baseUrl . '/oauth2/token',
                'Client_ID' => $clientId
            ]);
        }

        $accessToken = $tokenResponse->json()['access_token'];

        // 2. Recupera Utenti
        $usersResponse = Http::withToken($accessToken)
            ->withOptions(['verify' => false])
            ->get($baseUrl . '/scim2/Users');

        if ($usersResponse->failed()) {
            dd('WSO2 SCIM FAILED', [
                'Status' => $usersResponse->status(),
                'Response' => $usersResponse->json() ?? $usersResponse->body(),
                'Token_Usato' => substr($accessToken, 0, 10) . '...'
            ]);
        }

        return $usersResponse->json();
    }
}
