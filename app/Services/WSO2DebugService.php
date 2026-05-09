<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WSO2DebugService
{
    private string $baseUrl;
    private string $clientId;
    private string $clientSecret;

    public function __construct()
    {
        $this->baseUrl = config('services.oidc.base_url');
        $this->clientId = config('services.oidc.client_id');
        $this->clientSecret = config('services.oidc.client_secret');
    }

    /**
     * Debug completo del processo di autenticazione WSO2
     */
    public function debugAuthentication(): array
    {
        $debug = [
            'timestamp' => now()->toISOString(),
            'config' => [
                'base_url' => $this->baseUrl,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret ? '[SET]' : '[NOT_SET]',
            ],
            'steps' => []
        ];

        try {
            // Step 1: Test connessione base
            $debug['steps'][] = $this->testConnection();

            // Step 2: Test autenticazione client credentials
            $debug['steps'][] = $this->testClientCredentials();

            // Step 3: Test discovery endpoint
            $debug['steps'][] = $this->testDiscoveryEndpoint();

            // Step 4: Test userinfo endpoint (se access token disponibile)
            if (isset($debug['steps'][1]['response']['access_token'])) {
                $debug['steps'][] = $this->testUserInfo($debug['steps'][1]['response']['access_token']);
            }

            // Step 5: Test users endpoint
            if (isset($debug['steps'][1]['response']['access_token'])) {
                $debug['steps'][] = $this->testUsersEndpoint($debug['steps'][1]['response']['access_token']);
            }

            $debug['success'] = true;

        } catch (\Exception $e) {
            $debug['error'] = [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ];
            $debug['success'] = false;
        }

        return $debug;
    }

    /**
     * Test connessione base al server WSO2
     */
    private function testConnection(): array
    {
        $step = [
            'name' => 'Test Connessione Base',
            'url' => $this->baseUrl,
            'method' => 'GET'
        ];

        try {
            $response = Http::timeout(10)->get($this->baseUrl);
            
            $step['response'] = [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->body(),
                'successful' => $response->successful(),
                'client_error' => $response->clientError(),
                'server_error' => $response->serverError(),
            ];

            if ($response->successful()) {
                $step['success'] = true;
                $step['message'] = 'Connessione al server WSO2 riuscita';
            } else {
                $step['success'] = false;
                $step['message'] = 'Connessione fallita: ' . $response->status();
            }

        } catch (\Exception $e) {
            $step['success'] = false;
            $step['error'] = $e->getMessage();
            $step['message'] = 'Eccezione durante la connessione: ' . $e->getMessage();
        }

        return $step;
    }

    /**
     * Test autenticazione con client credentials
     */
    private function testClientCredentials(): array
    {
        $tokenUrl = $this->baseUrl . '/oauth2/token';
        $step = [
            'name' => 'Test Client Credentials',
            'url' => $tokenUrl,
            'method' => 'POST',
            'payload' => [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret ? '[HIDDEN]' : '[NOT_SET]',
                'scope' => 'internal_user_mgt_list internal_application_mgt_list'
            ]
        ];

        try {
            $response = Http::asForm()->timeout(10)->post($tokenUrl, [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'scope' => 'internal_user_mgt_list internal_application_mgt_list'
            ]);
            
            $step['response'] = [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->body(),
                'successful' => $response->successful(),
                'client_error' => $response->clientError(),
                'server_error' => $response->serverError(),
            ];

            if ($response->successful()) {
                $data = $response->json();
                $step['success'] = true;
                $step['message'] = 'Autenticazione riuscita';
                $step['token_info'] = [
                    'access_token' => substr($data['access_token'] ?? '', 0, 50) . '...',
                    'token_type' => $data['token_type'] ?? 'unknown',
                    'expires_in' => $data['expires_in'] ?? 'unknown',
                    'scope' => $data['scope'] ?? 'unknown',
                ];
            } else {
                $step['success'] = false;
                $step['message'] = 'Autenticazione fallita: ' . $response->status();
                $step['error_details'] = $response->json();
            }

        } catch (\Exception $e) {
            $step['success'] = false;
            $step['error'] = $e->getMessage();
            $step['message'] = 'Eccezione durante autenticazione: ' . $e->getMessage();
        }

        return $step;
    }

    /**
     * Test discovery endpoint OIDC
     */
    private function testDiscoveryEndpoint(): array
    {
        $discoveryUrl = $this->baseUrl . '/.well-known/openid_configuration';
        $step = [
            'name' => 'Test Discovery Endpoint',
            'url' => $discoveryUrl,
            'method' => 'GET'
        ];

        try {
            $response = Http::timeout(10)->get($discoveryUrl);
            
            $step['response'] = [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->body(),
                'successful' => $response->successful(),
            ];

            if ($response->successful()) {
                $data = $response->json();
                $step['success'] = true;
                $step['message'] = 'Discovery endpoint funzionante';
                $step['endpoints'] = [
                    'issuer' => $data['issuer'] ?? 'N/A',
                    'authorization_endpoint' => $data['authorization_endpoint'] ?? 'N/A',
                    'token_endpoint' => $data['token_endpoint'] ?? 'N/A',
                    'userinfo_endpoint' => $data['userinfo_endpoint'] ?? 'N/A',
                    'jwks_uri' => $data['jwks_uri'] ?? 'N/A',
                ];
            } else {
                $step['success'] = false;
                $step['message'] = 'Discovery endpoint non disponibile: ' . $response->status();
            }

        } catch (\Exception $e) {
            $step['success'] = false;
            $step['error'] = $e->getMessage();
            $step['message'] = 'Eccezione discovery endpoint: ' . $e->getMessage();
        }

        return $step;
    }

    /**
     * Test userinfo endpoint
     */
    private function testUserInfo(string $accessToken): array
    {
        $userinfoUrl = $this->baseUrl . '/oauth2/userinfo';
        $step = [
            'name' => 'Test Userinfo Endpoint',
            'url' => $userinfoUrl,
            'method' => 'GET',
            'token_preview' => substr($accessToken, 0, 50) . '...'
        ];

        try {
            $response = Http::withToken($accessToken)->timeout(10)->get($userinfoUrl);
            
            $step['response'] = [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->body(),
                'successful' => $response->successful(),
            ];

            if ($response->successful()) {
                $step['success'] = true;
                $step['message'] = 'Userinfo endpoint funzionante';
                $step['user_data'] = $response->json();
            } else {
                $step['success'] = false;
                $step['message'] = 'Userinfo endpoint fallito: ' . $response->status();
            }

        } catch (\Exception $e) {
            $step['success'] = false;
            $step['error'] = $e->getMessage();
            $step['message'] = 'Eccezione userinfo endpoint: ' . $e->getMessage();
        }

        return $step;
    }

    /**
     * Test users management endpoint
     */
    private function testUsersEndpoint(string $accessToken): array
    {
        $usersUrl = $this->baseUrl . '/api/server/v1/users';
        $step = [
            'name' => 'Test Users Management Endpoint',
            'url' => $usersUrl,
            'method' => 'GET',
            'token_preview' => substr($accessToken, 0, 50) . '...'
        ];

        try {
            $response = Http::withToken($accessToken)->timeout(10)->get($usersUrl);
            
            $step['response'] = [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->body(),
                'successful' => $response->successful(),
            ];

            if ($response->successful()) {
                $users = $response->json();
                $step['success'] = true;
                $step['message'] = 'Users endpoint funzionante';
                $step['users_count'] = is_array($users) ? count($users) : 'N/A';
                $step['users_sample'] = is_array($users) ? array_slice($users, 0, 3) : $users;
            } else {
                $step['success'] = false;
                $step['message'] = 'Users endpoint fallito: ' . $response->status();
                $step['error_details'] = $response->json();
            }

        } catch (\Exception $e) {
            $step['success'] = false;
            $step['error'] = $e->getMessage();
            $step['message'] = 'Eccezione users endpoint: ' . $e->getMessage();
        }

        return $step;
    }

    /**
     * Log completo del debug
     */
    public function logDebug(): void
    {
        $debug = $this->debugAuthentication();
        
        Log::info('WSO2 Debug Report', [
            'debug' => $debug
        ]);

        // Salva anche su file per analisi manuale
        $logFile = storage_path('logs/wso2_debug_' . now()->format('Y-m-d_H-i-s') . '.json');
        file_put_contents($logFile, json_encode($debug, JSON_PRETTY_PRINT));
        
        Log::info("WSO2 Debug salvato in: {$logFile}");
    }
}
