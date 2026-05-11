<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DellOmeSimpleService
{
    /**
     * Ottiene il token di sessione da Dell OME
     */
    private function getSessionToken(): ?string
    {
        try {
            Log::debug('Dell OME Simple - Tentativo autenticazione', [
                'host' => env('DELL_OME_IP'),
                'username' => env('DELL_OME_USER'),
                'timestamp' => now()->toISOString()
            ]);

            $response = Http::withOptions(['verify' => false])  // Necessario se il certificato è self-signed
                ->timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ])
                ->post('https://' . env('DELL_OME_IP') . '/api/SessionService/Sessions', [
                    'UserName' => env('DELL_OME_USER'),
                    'Password' => env('DELL_OME_PASS'),
                    'SessionType' => 'API'
                ]);

            Log::debug('Dell OME Simple - Risposta autenticazione', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'headers' => $response->headers(),
                'response_time' => $response->handlerStats()['total_time'] ?? 'N/A'
            ]);

            if ($response->successful()) {
                $authToken = $response->header('X-Auth-Token');

                if ($authToken) {
                    Log::info('Dell OME Simple - Autenticazione riuscita', [
                        'token_length' => strlen($authToken)
                    ]);
                    return $authToken;
                }
            }

            Log::error('Dell OME Simple - Autenticazione fallita', [
                'status' => $response->status(),
                'body' => $response->body(),
                'headers' => $response->headers(),
                'error' => $response->reason() ?? 'Unknown error'
            ]);
        } catch (\Exception $e) {
            Log::error('Dell OME Simple - Eccezione durante autenticazione', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return null;
    }

    /**
     * Esporta i dati dei dispositivi da Dell OME
     */
    public function getDevices()
    {
        try {
            Log::debug('Dell OME Simple - Inizio recupero dispositivi', [
                'timestamp' => now()->toISOString()
            ]);

            // 1. Ottieni il Token di Sessione
            $authToken = $this->getSessionToken();

            if (!$authToken) {
                Log::warning('Dell OME Simple - Token non disponibile');
                return null;
            }

            Log::debug('Dell OME Simple - Recupero dispositivi', [
                'url' => 'https://' . env('DELL_OME_IP') . '/api/DeviceService/Devices'
            ]);

            // 2. Esporta i dati dei dispositivi (DeviceService)
            $response = Http::withOptions(['verify' => false])
                ->timeout(30)
                ->withHeaders([
                    'X-Auth-Token' => $authToken,
                    'Accept' => 'application/json'
                ])
                ->get('https://' . env('DELL_OME_IP') . '/api/DeviceService/Devices');

            Log::debug('Dell OME Simple - Risposta dispositivi', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'response_time' => $response->handlerStats()['total_time'] ?? 'N/A'
            ]);

            if ($response->successful()) {
                $devices = $response->json();

                Log::info('Dell OME Simple - Dispositivi recuperati con successo', [
                    'count' => isset($devices['value']) ? count($devices['value']) : 0
                ]);

                return $devices;
            } else {
                Log::error('Dell OME Simple - Errore recupero dispositivi', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'error' => $response->reason() ?? 'Unknown error'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Dell OME Simple - Eccezione durante recupero dispositivi', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return null;
    }

    /**
     * Esporta le garanzie dei dispositivi
     */
    public function getWarranties()
    {
        try {
            Log::debug('Dell OME Simple - Inizio recupero garanzie', [
                'timestamp' => now()->toISOString()
            ]);

            $authToken = $this->getSessionToken();

            if (!$authToken) {
                Log::warning('Dell OME Simple - Token non disponibile per garanzie');
                return null;
            }

            Log::debug('Dell OME Simple - Recupero garanzie', [
                'url' => 'https://' . env('DELL_OME_IP') . '/api/AssetAdvisorService/Warranties'
            ]);

            $response = Http::withOptions(['verify' => false])
                ->timeout(30)
                ->withHeaders([
                    'X-Auth-Token' => $authToken,
                    'Accept' => 'application/json'
                ])
                ->get('https://' . env('DELL_OME_IP') . '/api/AssetAdvisorService/Warranties');

            Log::debug('Dell OME Simple - Risposta garanzie', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'response_time' => $response->handlerStats()['total_time'] ?? 'N/A'
            ]);

            if ($response->successful()) {
                $warranties = $response->json();

                Log::info('Dell OME Simple - Garanzie recuperate con successo', [
                    'count' => isset($warranties['value']) ? count($warranties['value']) : 0
                ]);

                return $warranties;
            } else {
                Log::error('Dell OME Simple - Errore recupero garanzie', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'error' => $response->reason() ?? 'Unknown error'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Dell OME Simple - Eccezione durante recupero garanzie', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return null;
    }

    /**
     * Metodo di diagnostica per verificare la connessione
     */
    public function diagnoseConnection(): array
    {
        $diagnosis = [
            'timestamp' => now()->toISOString(),
            'config' => [
                'host' => env('DELL_OME_IP'),
                'username' => env('DELL_OME_USER'),
                'host_set' => !empty(env('DELL_OME_IP')),
                'username_set' => !empty(env('DELL_OME_USER')),
                'password_set' => !empty(env('DELL_OME_PASS')),
            ],
            'tests' => []
        ];

        // Test 1: Verifica configurazione
        $diagnosis['tests']['config'] = [
            'status' => (!empty(env('DELL_OME_IP')) && !empty(env('DELL_OME_USER')) && !empty(env('DELL_OME_PASS'))) ? 'PASS' : 'FAIL',
            'message' => 'Verifica parametri .env'
        ];

        // Test 2: Connessione HTTP base
        try {
            $host = env('DELL_OME_IP');
            if (!$host) {
                $diagnosis['tests']['http'] = [
                    'status' => 'FAIL',
                    'message' => 'DELL_OME_IP non configurato nel .env'
                ];
            } else {
                $startTime = microtime(true);
                $response = Http::withOptions(['verify' => false])
                    ->timeout(10)
                    ->get('https://' . $host);

                $responseTime = round((microtime(true) - $startTime) * 1000, 2);

                $diagnosis['tests']['http'] = [
                    'status' => $response->successful() ? 'PASS' : 'FAIL',
                    'message' => "HTTP {$response->status()} - {$response->reason()}",
                    'response_time_ms' => $responseTime
                ];
            }
        } catch (\Exception $e) {
            $diagnosis['tests']['http'] = [
                'status' => 'ERROR',
                'message' => $e->getMessage()
            ];
        }

        // Test 3: Autenticazione
        try {
            $token = $this->getSessionToken();
            $diagnosis['tests']['auth'] = [
                'status' => $token ? 'PASS' : 'FAIL',
                'message' => $token ? 'Autenticazione riuscita' : 'Autenticazione fallita'
            ];
        } catch (\Exception $e) {
            $diagnosis['tests']['auth'] = [
                'status' => 'ERROR',
                'message' => $e->getMessage()
            ];
        }

        Log::info('Dell OME Simple - Diagnosi connessione completata', $diagnosis);

        return $diagnosis;
    }
}
