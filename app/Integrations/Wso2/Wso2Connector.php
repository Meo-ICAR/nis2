<?php

namespace App\Integrations\Wso2;

use App\Contracts\Integration\SubsystemConnector;
use App\Models\Application;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Wso2Connector implements SubsystemConnector
{
    public function __construct(
        protected Application $application
    ) {}

    /**
     * Get the WSO2 configuration from the application's integration_config.
     */
    protected function getConfig(): array
    {
        return array_merge([
            'base_url' => $this->application->wso2_base_url ?? '',
            'tenant_domain' => $this->application->wso2_tenant_domain ?? 'carbon.super',
            'token_endpoint' => '/oauth2/token',
            'api_endpoint' => '/api/server/v1',
        ], $this->application->integration_config ?? []);
    }

    /**
     * Obtain an OAuth2 Access Token using Client Credentials.
     */
    protected function getAccessToken(): ?string
    {
        $config = $this->getConfig();
        
        if (empty($config['base_url']) || empty($this->application->client_id)) {
            return null;
        }

        try {
            $response = Http::asForm()
                ->withBasicAuth($this->application->client_id, $this->application->client_secret)
                ->post(rtrim($config['base_url'], '/') . $config['token_endpoint'], [
                    'grant_type' => 'client_credentials',
                ]);

            if ($response->successful()) {
                return $response->json('access_token');
            }

            Log::error("WSO2 Token Error for {$this->application->name}: " . $response->body());
        } catch (\Exception $e) {
            Log::error("WSO2 Connection Error for {$this->application->name}: " . $e->getMessage());
        }

        return null;
    }

    public function sync(): array
    {
        $token = $this->getAccessToken();
        
        if (!$token) {
            return ['status' => 'error', 'message' => 'Could not obtain access token'];
        }

        // Example: Sync Service Provider details or application metadata
        // For demonstration, we just return a success state with the token check
        return [
            'status' => 'success',
            'synced_at' => now()->toIso8601String(),
            'app_name' => $this->application->name,
            'connection_verified' => true,
        ];
    }

    public function getStatus(): string
    {
        $config = $this->getConfig();
        
        if (empty($config['base_url'])) {
            return 'not_configured';
        }

        try {
            $response = Http::timeout(5)->get(rtrim($config['base_url'], '/') . '/.well-known/openid-configuration');
            
            return $response->successful() ? 'online' : 'unreachable';
        } catch (\Exception $e) {
            return 'offline';
        }
    }
}
