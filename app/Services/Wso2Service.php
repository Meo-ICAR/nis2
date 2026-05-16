<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class Wso2Service
{
    public function getProjects()
    {
        // 1. URL per l'Autenticazione (dal tuo codice)
        $authBaseUrl = config('services.wso2.auth_base_url');  // es. https://auth-fossr.na.icar.cnr.it

        $tokenResponse = Http::asForm()
            ->withOptions(['verify' => false])
            ->withBasicAuth(config('services.wso2.client_id'), config('services.wso2.client_secret'))
            ->post($authBaseUrl . '/oauth2/token', [
                'grant_type' => 'client_credentials',
                //  'scope'      => 'internal_application_mgt_view', // Assicurati che questo scope serva davvero!
            ]);

        $accessToken = $tokenResponse->json()['access_token'] ?? null;

        if (!$accessToken) {
            return [
                'error' => 'Recupero Token fallito',
                'details' => $tokenResponse->body()
            ];
        }

        // 2. URL per le API vere e proprie
        $apiBaseUrl = config('services.wso2.api_base_url');  // es. https://fossr-gateway.na.icar.cnr.it/sshocmarketplaceapi/0.1.1/api

        // Completamento della tua chiamata
        $response = Http::withToken($accessToken)
            ->withOptions(['verify' => false])  // Disabilita SSL anche per il gateway, per sicurezza
            ->withHeaders([
                'Accept' => 'application/json',
            ])
            ->get($apiBaseUrl . '/projects');  // Cambia '/projects' con '/datasets' se necessario

        if ($response->failed()) {
            return [
                'error' => 'Errore nella lettura dei progetti',
                'status' => $response->status(),
                'details' => $response->body()
            ];
        }

        // Ritorna i dati in formato Array/JSON
        return $response->json();
    }

    /**
     * Recupera il Token da WSO2 usando il Client Credentials Grant
     */
    public function getToken(): string
    {
        // Se il token è già in cache e non è scaduto, usa quello
        return Cache::remember('wso2_access_token', 3000, function () {
            // Chiamata all'endpoint Token usando SOLO Client ID e Client Secret
            $response = Http::asForm()
                ->withoutVerifying()  // Aggiungilo se hai problemi di certificati SSL (come il -k del curl)
                ->withBasicAuth(env('WSO2_CLIENT_ID'), env('WSO2_CLIENT_SECRET'))
                ->post(env('WSO2_TOKEN_URL'), [
                    'grant_type' => 'client_credentials',
                    // Non passiamo più username e password qui!
                ]);

            if ($response->failed()) {
                throw new Exception('Errore nel recupero del Token WSO2: ' . $response->body());
            }

            // Ritorna l'access token
            return $response->json('access_token');
        });
    }

    public function getApplicationsWithUrls()
    {
        $baseUrl = config('services.wso2.base_url');

        $tokenResponse = Http::asForm()
            ->withOptions(['verify' => false])
            ->withBasicAuth(config('services.wso2.client_id'), config('services.wso2.client_secret'))
            ->post($baseUrl . '/oauth2/token', [
                'grant_type' => 'client_credentials',
                'scope' => 'internal_application_mgt_view',
            ]);

        $accessToken = $tokenResponse->json()['access_token'] ?? null;
        if (!$accessToken)
            return ['error' => 'Token fallito'];

        $response = Http::withToken($accessToken)
            ->withOptions(['verify' => false])
            ->get($baseUrl . '/api/server/v1/applications');

        $applications = $response->json()['applications'] ?? [];
        $detailedApps = [];

        foreach ($applications as $app) {
            $detailsResponse = Http::withToken($accessToken)
                ->withOptions(['verify' => false])
                ->get($baseUrl . '/api/server/v1/applications/' . $app['id']);

            $details = $detailsResponse->json();
            $callbackUrls = [];

            // In Wso2Service.php, dentro il ciclo foreach delle applicazioni:

            if (!empty($details['inboundProtocols'])) {
                foreach ($details['inboundProtocols'] as $protocol) {
                    if ($protocol['type'] === 'oauth2') {
                        $protocolDetails = Http::withToken($accessToken)
                            ->withOptions(['verify' => false])
                            ->get($baseUrl . $protocol['self'])
                            ->json();

                        if (isset($protocolDetails['callbackURLs'])) {
                            $rawUrls = $protocolDetails['callbackURLs'];
                            $processedUrls = [];

                            foreach ($rawUrls as $urlItem) {
                                // Se l'URL inizia con regexp=, dobbiamo pulirlo e dividerlo
                                if (str_starts_with($urlItem, 'regexp=')) {
                                    // Rimuoviamo 'regexp=(' all'inizio e ')' alla fine
                                    $cleanRegex = str_replace(['regexp=(', ')'], '', $urlItem);
                                    // Dividiamo i vari URL separati dal simbolo pipe |
                                    $individualUrls = explode('|', $cleanRegex);
                                } else {
                                    $individualUrls = [$urlItem];
                                }

                                // Ora filtriamo ogni singolo URL trovato
                                foreach ($individualUrls as $url) {
                                    // 1. Filtro base per localhost
                                    $isLocal = str_contains($url, 'localhost') || str_contains($url, '127.0.0.1');

                                    if (!$isLocal && !empty($url)) {
                                        // 2. Estraiamo solo schema e host (es. https e fossr-portale.na.icar.cnr.it)
                                        $parsed = parse_url($url);

                                        if (isset($parsed['scheme']) && isset($parsed['host'])) {
                                            $baseUrlOnly = $parsed['scheme'] . '://' . $parsed['host'];
                                            $processedUrls[] = $baseUrlOnly;
                                        }
                                    }
                                }
                            }

                            // Rimuoviamo duplicati e assegniamo
                            $callbackUrls = array_unique($processedUrls);
                        }
                    }
                }
            }

            // Aggiungiamo l'app alla lista solo se ha degli URL validi dopo il filtro
            // (Opzionale: togli il check !empty se vuoi vedere anche le app svuotate dal filtro)
            if (!empty($callbackUrls)) {
                $detailedApps[] = [
                    'id' => $app['id'],
                    'name' => $app['name'],
                    'urls' => array_values($callbackUrls)  // reset degli indici array
                ];
            }
        }

        return $detailedApps;
    }
}
