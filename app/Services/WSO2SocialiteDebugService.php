<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\ProviderInterface;

class WSO2SocialiteDebugService
{
    /**
     * Debug completo del processo Socialite di login
     */
    public function debugSocialiteLogin(): array
    {
        $debug = [
            'timestamp' => now()->toISOString(),
            'steps' => []
        ];

        try {
            // Step 1: Test redirect URL generation
            $debug['steps'][] = $this->testRedirectGeneration();

            // Step 2: Test configuration
            $debug['steps'][] = $this->testSocialiteConfig();

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
     * Test generazione URL di redirect
     */
    private function testRedirectGeneration(): array
    {
        $step = [
            'name' => 'Test Redirect URL Generation'
        ];

        try {
            $provider = Socialite::driver('oidc');
            
            // Test base redirect
            $redirectUrl = $provider->stateless()->redirect()->getTargetUrl();
            
            $step['success'] = true;
            $step['message'] = 'Redirect URL generato con successo';
            $step['redirect_url'] = $redirectUrl;
            
            // Analizza l'URL generato
            $parsedUrl = parse_url($redirectUrl);
            $step['url_components'] = [
                'scheme' => $parsedUrl['scheme'] ?? 'N/A',
                'host' => $parsedUrl['host'] ?? 'N/A',
                'path' => $parsedUrl['path'] ?? 'N/A',
                'query' => $parsedUrl['query'] ?? 'N/A',
            ];
            
            // Estrai parametri query
            if (isset($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $queryParams);
                $step['query_params'] = $queryParams;
                
                // Nascondi valori sensibili
                if (isset($step['query_params']['client_secret'])) {
                    $step['query_params']['client_secret'] = '[HIDDEN]';
                }
            }

        } catch (\Exception $e) {
            $step['success'] = false;
            $step['error'] = $e->getMessage();
            $step['message'] = 'Errore generazione redirect: ' . $e->getMessage();
        }

        return $step;
    }

    /**
     * Test configurazione Socialite
     */
    private function testSocialiteConfig(): array
    {
        $step = [
            'name' => 'Test Socialite Configuration'
        ];

        try {
            $config = config('services.oidc');
            
            $step['config'] = [
                'base_url' => $config['base_url'] ?? 'NOT_SET',
                'client_id' => $config['client_id'] ?? 'NOT_SET',
                'client_secret' => $config['client_secret'] ? '[SET]' : '[NOT_SET]',
                'redirect' => $config['redirect'] ?? 'NOT_SET',
                'scopes' => $config['scopes'] ?? ['default'],
            ];
            
            $step['success'] = true;
            $step['message'] = 'Configurazione Socialite caricata';
            
            // Verifica campi obbligatori
            $required = ['base_url', 'client_id', 'client_secret', 'redirect'];
            $missing = [];
            
            foreach ($required as $field) {
                if (empty($config[$field])) {
                    $missing[] = $field;
                }
            }
            
            if (!empty($missing)) {
                $step['success'] = false;
                $step['message'] = 'Campi obbligatori mancanti: ' . implode(', ', $missing);
            }

        } catch (\Exception $e) {
            $step['success'] = false;
            $step['error'] = $e->getMessage();
            $step['message'] = 'Errore configurazione: ' . $e->getMessage();
        }

        return $step;
    }

    /**
     * Debug del callback Socialite
     */
    public function debugSocialiteCallback(): array
    {
        $debug = [
            'timestamp' => now()->toISOString(),
            'steps' => []
        ];

        try {
            // Step 1: Test recupero utente
            $debug['steps'][] = $this->testUserRetrieval();

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
     * Test recupero utente da Socialite
     */
    private function testUserRetrieval(): array
    {
        $step = [
            'name' => 'Test User Retrieval from Socialite'
        ];

        try {
            $user = Socialite::driver('oidc')->stateless()->user();
            
            $step['success'] = true;
            $step['message'] = 'Utente recuperato con successo';
            $step['user_data'] = [
                'id' => $user->getId(),
                'nickname' => $user->getNickname(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'avatar' => $user->getAvatar(),
            ];
            
            // Mostra tutti gli attributi
            $step['raw_user'] = $user->getRaw();
            
            // Mostra token info (safely)
            $step['token_info'] = [
                'token' => substr($user->token ?? '', 0, 50) . '...',
                'refreshToken' => $user->refreshToken ? '[SET]' : '[NOT_SET]',
                'expiresIn' => $user->expiresIn ?? 'N/A',
            ];

        } catch (InvalidStateException $e) {
            $step['success'] = false;
            $step['error_type'] = 'InvalidStateException';
            $step['error'] = $e->getMessage();
            $step['message'] = 'Stato OAuth2 non valido - possibile CSRF o session scaduta';
            
        } catch (\Exception $e) {
            $step['success'] = false;
            $step['error'] = $e->getMessage();
            $step['message'] = 'Errore recupero utente: ' . $e->getMessage();
        }

        return $step;
    }

    /**
     * Log completo del debug Socialite
     */
    public function logSocialiteDebug(string $type = 'login'): void
    {
        $debug = $type === 'callback' ? $this->debugSocialiteCallback() : $this->debugSocialiteLogin();
        
        Log::info("WSO2 Socialite Debug ({$type})", [
            'debug' => $debug
        ]);

        // Salva anche su file per analisi manuale
        $logFile = storage_path("logs/wso2_socialite_debug_{$type}_" . now()->format('Y-m-d_H-i-s') . '.json');
        file_put_contents($logFile, json_encode($debug, JSON_PRETTY_PRINT));
        
        Log::info("WSO2 Socialite Debug ({$type}) salvato in: {$logFile}");
    }
}
