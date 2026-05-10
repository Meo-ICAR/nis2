<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WSO2UserService
{
    private string $baseUrl;
    private string $scimUsername;
    private string $scimPassword;

    public function __construct()
    {
        $this->baseUrl = config('services.oidc.base_url');
        $this->scimUsername = config('services.oidc.scim_username') ?: 'tuo_utente_admin';
        $this->scimPassword = config('services.oidc.scim_password') ?: 'tua_password_admin';
    }

    /**
     * Scarica gli utenti amministratori da WSO2 e li inserisce nella tabella users
     */
    public function syncAdminUsers(): array
    {
        try {
            // Scarica gli utenti da WSO2 via SCIM2
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
            Log::error('Errore durante il sync degli utenti WSO2: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Scarica gli utenti con privilegi di amministratore da WSO2 via SCIM2
     */
    private function fetchAdminUsers(): array
    {
        $response = Http::withBasicAuth($this->scimUsername, $this->scimPassword)
            ->withHeaders([
                'Accept' => 'application/scim+json',
            ])
            ->get($this->baseUrl . '/scim2/Users', [
                'startIndex' => 1,
                'count' => 100,
            ]);

        if (!$response->successful()) {
            throw new \Exception('Recupero utenti WSO2 SCIM2 fallito: ' . $response->body());
        }

        $data = $response->json();
        $users = $data['Resources'] ?? [];

        // Filtra solo gli utenti amministratori
        return array_filter($users, function ($user) {
            return $this->isAdminUser($user);
        });
    }

    /**
     * Verifica se l'utente ha privilegi di amministratore (formato SCIM2)
     */
    private function isAdminUser(array $user): bool
    {
        // Controlla se l'utente ha il ruolo admin in SCIM2 groups
        if (isset($user['groups']) && is_array($user['groups'])) {
            foreach ($user['groups'] as $group) {
                if (isset($group['display']) &&
                        in_array(strtolower($group['display']), ['admin', 'administrator', 'internal/admin'])) {
                    return true;
                }
            }
        }

        // Controlla il nome utente per pattern admin
        if (isset($user['userName']) &&
                preg_match('/admin/i', $user['userName'])) {
            return true;
        }

        // Controlla se l'email contiene admin
        if (isset($user['emails']) && is_array($user['emails'])) {
            foreach ($user['emails'] as $email) {
                if (isset($email['value']) &&
                        preg_match('/admin/i', $email['value'])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Crea o aggiorna un utente nel database locale (formato SCIM2)
     */
    private function createOrUpdateUser(array $wso2User): User
    {
        $email = $this->extractEmail($wso2User);
        $name = $this->extractName($wso2User);
        $sub = $wso2User['id'] ?? $wso2User['userName'] ?? null;

        return User::updateOrCreate(
            ['sub' => $sub],
            [
                'name' => $name,
                'email' => $email,
                'is_admin' => true,
                'is_active' => true,
                'email_verified_at' => now(),
                'last_login_at' => null,
            ]
        );
    }

    /**
     * Estrae l'email dall'utente WSO2 (formato SCIM2)
     */
    private function extractEmail(array $user): string
    {
        // SCIM2: emails è un array di oggetti con 'value' e 'type'
        if (isset($user['emails']) && is_array($user['emails'])) {
            foreach ($user['emails'] as $email) {
                if (isset($email['value']) && !empty($email['value'])) {
                    return $email['value'];
                }
            }
        }

        // Fallback: userName con domain
        $username = $user['userName'] ?? $user['name'] ?? 'unknown';

        // Se lo username contiene già @, usalo come email
        if (strpos($username, '@') !== false) {
            return $username;
        }

        // Altrimenti aggiungi un domain di default
        return $username . '@icar.cnr.it';
    }

    /**
     * Estrae il nome completo dall'utente WSO2 (formato SCIM2)
     */
    private function extractName(array $user): string
    {
        // SCIM2: displayName è il campo principale
        if (!empty($user['displayName'])) {
            return $user['displayName'];
        }

        // SCIM2: name è un oggetto con givenName e familyName
        if (isset($user['name']) && is_array($user['name'])) {
            $nameObj = $user['name'];
            if (!empty($nameObj['givenName']) || !empty($nameObj['familyName'])) {
                return trim(($nameObj['givenName'] ?? '') . ' ' . ($nameObj['familyName'] ?? ''));
            }
        }

        // Fallback: userName
        return $user['userName'] ?? $user['name'] ?? 'Unknown User';
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
