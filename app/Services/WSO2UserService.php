<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WSO2UserService
{
    private string $baseUrl;
    private string $clientId;
    private string $clientSecret;
    private ?string $accessToken = null;

    public function __construct()
    {
        $this->baseUrl = config('services.oidc.base_url');
        $this->clientId = config('services.oidc.client_id');
        $this->clientSecret = config('services.oidc.client_secret');
    }

    /**
     * Scarica gli utenti amministratori da WSO2 e li inserisce nella tabella users
     */
    public function syncAdminUsers(): array
    {
        try {
            // Ottieni l'access token
            $this->authenticate();

            // Scarica gli utenti da WSO2
            $wso2Users = $this->fetchAdminUsers();

            $synced = [];
            $updated = 0;
            $created = 0;

            foreach ($wso2Users as $wso2User) {
                $user = $this->createOrUpdateUser($wso2User);
                
                if ($user->wasRecentlyCreated) {
                    $created++;
                    Log::info("Nuovo utente amministratore creato: {$user->email}");
                } else {
                    $updated++;
                    Log::info("Utente amministratore aggiornato: {$user->email}");
                }

                $synced[] = $user;
            }

            Log::info("Sync WSO2 completato: {$created} creati, {$updated} aggiornati");

            return [
                'synced' => $synced,
                'created' => $created,
                'updated' => $updated,
                'total' => count($synced)
            ];

        } catch (\Exception $e) {
            Log::error("Errore durante il sync degli utenti WSO2: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Autentica con WSO2 per ottenere l'access token
     */
    private function authenticate(): void
    {
        $response = Http::asForm()->post($this->baseUrl . '/oauth2/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'scope' => 'internal_user_mgt_list'
        ]);

        if (!$response->successful()) {
            throw new \Exception("Autenticazione WSO2 fallita: " . $response->body());
        }

        $data = $response->json();
        $this->accessToken = $data['access_token'];
    }

    /**
     * Scarica gli utenti con privilegi di amministratore da WSO2
     */
    private function fetchAdminUsers(): array
    {
        if (!$this->accessToken) {
            throw new \Exception("Access token non disponibile");
        }

        // Endpoint per ottenere la lista degli utenti
        $response = Http::withToken($this->accessToken)
            ->get($this->baseUrl . '/api/server/v1/users');

        if (!$response->successful()) {
            throw new \Exception("Recupero utenti WSO2 fallito: " . $response->body());
        }

        $users = $response->json();

        // Filtra solo gli utenti amministratori
        return array_filter($users, function ($user) {
            return $this->isAdminUser($user);
        });
    }

    /**
     * Verifica se l'utente ha privilegi di amministratore
     */
    private function isAdminUser(array $user): bool
    {
        // Controlla se l'utente ha il ruolo admin
        if (isset($user['roles']) && is_array($user['roles'])) {
            foreach ($user['roles'] as $role) {
                if (in_array(strtolower($role), ['admin', 'administrator', 'internal/admin'])) {
                    return true;
                }
            }
        }

        // Controlla claim specifici per admin
        if (isset($user['claims']) && is_array($user['claims'])) {
            foreach ($user['claims'] as $claim => $value) {
                if (in_array(strtolower($claim), ['admin', 'is_admin', 'role']) && 
                    (is_bool($value) ? $value : in_array(strtolower($value), ['admin', 'true', '1']))) {
                    return true;
                }
            }
        }

        // Controlla il nome utente per pattern admin
        if (isset($user['username']) && 
            preg_match('/admin/i', $user['username'])) {
            return true;
        }

        return false;
    }

    /**
     * Crea o aggiorna un utente nel database locale
     */
    private function createOrUpdateUser(array $wso2User): User
    {
        $email = $this->extractEmail($wso2User);
        $name = $this->extractName($wso2User);
        $sub = $wso2User['sub'] ?? $wso2User['id'] ?? $wso2User['username'] ?? null;

        return User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'sub' => $sub,
                'is_admin' => true,
                'is_active' => true,
                'email_verified_at' => now(),
                'last_login_at' => null,
            ]
        );
    }

    /**
     * Estrae l'email dall'utente WSO2
     */
    private function extractEmail(array $user): string
    {
        // Priorità: email, username con domain, username
        if (!empty($user['email'])) {
            return $user['email'];
        }

        $username = $user['username'] ?? $user['name'] ?? 'unknown';
        
        // Se lo username contiene già @, usalo come email
        if (strpos($username, '@') !== false) {
            return $username;
        }

        // Altrimenti aggiungi un domain di default
        return $username . '@wso2.local';
    }

    /**
     * Estrae il nome completo dall'utente WSO2
     */
    private function extractName(array $user): string
    {
        // Priorità: displayName, givenName + familyName, username
        if (!empty($user['displayName'])) {
            return $user['displayName'];
        }

        if (!empty($user['givenName']) || !empty($user['familyName'])) {
            return trim(($user['givenName'] ?? '') . ' ' . ($user['familyName'] ?? ''));
        }

        return $user['username'] ?? $user['name'] ?? 'Unknown User';
    }

    /**
     * Ottieni statistiche sul sync
     */
    public function getSyncStats(): array
    {
        $totalAdmins = User::where('is_admin', true)->count();
        $activeAdmins = User::where('is_admin', true)->where('is_active', true)->count();
        
        return [
            'total_admins' => $totalAdmins,
            'active_admins' => $activeAdmins,
            'inactive_admins' => $totalAdmins - $activeAdmins,
        ];
    }
}
