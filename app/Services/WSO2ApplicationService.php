<?php

namespace App\Services;

use App\Models\Application;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WSO2ApplicationService
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
     * Scarica le applicazioni da WSO2 e le inserisce nella tabella applications
     */
    public function syncApplications(): array
    {
        try {
            // Ottieni l'access token
            $this->authenticate();

            // Scarica le applicazioni da WSO2
            $wso2Applications = $this->fetchApplications();

            $synced = [];
            $updated = 0;
            $created = 0;
            $skipped = 0;

            foreach ($wso2Applications as $wso2App) {
                try {
                    $application = $this->createOrUpdateApplication($wso2App);
                    
                    if ($application->wasRecentlyCreated) {
                        $created++;
                        Log::info("Nuova applicazione creata: {$application->name}");
                    } else {
                        $updated++;
                        Log::info("Applicazione aggiornata: {$application->name}");
                    }

                    $synced[] = $application;

                } catch (\Exception $e) {
                    $skipped++;
                    Log::warning("Applicazione saltata: " . $e->getMessage());
                }
            }

            Log::info("Sync applicazioni WSO2 completato: {$created} create, {$updated} aggiornate, {$skipped} saltate");

            return [
                'synced' => $synced,
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped,
                'total' => count($synced)
            ];

        } catch (\Exception $e) {
            Log::error("Errore durante il sync delle applicazioni WSO2: " . $e->getMessage());
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
            'scope' => 'internal_application_mgt_list internal_application_mgt_view'
        ]);

        if (!$response->successful()) {
            throw new \Exception("Autenticazione WSO2 fallita: " . $response->body());
        }

        $data = $response->json();
        $this->accessToken = $data['access_token'];
    }

    /**
     * Scarica le applicazioni da WSO2
     */
    private function fetchApplications(): array
    {
        if (!$this->accessToken) {
            throw new \Exception("Access token non disponibile");
        }

        // Endpoint per ottenere la lista delle applicazioni OAuth2
        $response = Http::withToken($this->accessToken)
            ->get($this->baseUrl . '/api/server/v1/applications');

        if (!$response->successful()) {
            throw new \Exception("Recupero applicazioni WSO2 fallito: " . $response->body());
        }

        $applications = $response->json();

        // Se necessario, ottieni dettagli aggiuntivi per ogni applicazione
        return array_map([$this, 'enrichApplicationData'], $applications);
    }

    /**
     * Arricchisce i dati dell'applicazione con informazioni aggiuntive
     */
    private function enrichApplicationData(array $app): array
    {
        try {
            // Ottieni dettagli completi dell'applicazione
            $response = Http::withToken($this->accessToken)
                ->get($this->baseUrl . '/api/server/v1/applications/' . $app['applicationId']);

            if ($response->successful()) {
                $details = $response->json();
                return array_merge($app, $details);
            }
        } catch (\Exception $e) {
            Log::warning("Impossibile ottenere dettagli per applicazione {$app['applicationId']}: " . $e->getMessage());
        }

        return $app;
    }

    /**
     * Crea o aggiorna un'applicazione nel database locale
     */
    private function createOrUpdateApplication(array $wso2App): Application
    {
        $name = $this->extractApplicationName($wso2App);
        $clientId = $wso2App['clientId'] ?? null;
        
        // Trova l'applicazione esistente per clientId o name
        $existingApp = null;
        if ($clientId) {
            $existingApp = Application::where('client_id', $clientId)->first();
        }
        
        if (!$existingApp) {
            $existingApp = Application::where('name', $name)->first();
        }

        $data = [
            'name' => $name,
            'short_name' => $this->extractShortName($wso2App),
            'description' => $this->extractDescription($wso2App),
            'url' => $this->extractUrl($wso2App),
            'project' => $this->extractProject($wso2App),
            'category' => $this->extractCategory($wso2App),
            'icon_url' => $this->extractIconUrl($wso2App),
            'url_documentation' => $this->extractDocumentationUrl($wso2App),
            'is_active' => $this->isActive($wso2App),
            'is_strategic' => $this->isStrategic($wso2App),
            
            // NIS2 compliance fields
            'scientific_owner' => $this->extractScientificOwner($wso2App),
            'scientific_contact' => $this->extractScientificContact($wso2App),
            'url_cockpit' => $this->extractCockpitUrl($wso2App),
            'url_sandbox' => $this->extractSandboxUrl($wso2App),
            'internal_technical_contact' => $this->extractInternalTechnicalContact($wso2App),
            'external_technical_contact' => $this->extractExternalTechnicalContact($wso2App),
            'external_technical_email' => $this->extractExternalTechnicalEmail($wso2App),
            
            // OAuth2 fields
            'client_id' => $clientId,
            'client_secret' => $wso2App['clientSecret'] ?? null, // Attenzione: potrebbe essere mascherato
            
            // Contract fields (da WSO2 attributes se disponibili)
            'support_contract_expiry' => $this->extractContractExpiry($wso2App),
            'contract_notes' => $this->extractContractNotes($wso2App),
        ];

        if ($existingApp) {
            $existingApp->update($data);
            return $existingApp;
        } else {
            // Aggiungi sort_order se nuova applicazione
            $data['sort_order'] = Application::max('sort_order') + 1;
            return Application::create($data);
        }
    }

    /**
     * Estrae il nome dell'applicazione
     */
    private function extractApplicationName(array $app): string
    {
        return $app['name'] ?? $app['applicationName'] ?? $app['displayName'] ?? 'Unknown Application';
    }

    /**
     * Estrae il nome breve
     */
    private function extractShortName(array $app): ?string
    {
        $name = $this->extractApplicationName($app);
        $shortName = $app['shortName'] ?? null;
        
        if ($shortName) {
            return $shortName;
        }
        
        // Genera automaticamente dal nome
        return Str::limit($name, 20, '');
    }

    /**
     * Estrae la descrizione
     */
    private function extractDescription(array $app): ?string
    {
        return $app['description'] ?? $app['applicationDescription'] ?? null;
    }

    /**
     * Estrae l'URL principale
     */
    private function extractUrl(array $app): string
    {
        // Cerca in vari campi possibili
        $url = $app['url'] ?? $app['applicationUrl'] ?? $app['homepageUrl'] ?? null;
        
        if ($url) {
            return $url;
        }
        
        // Genera URL base dal nome se non disponibile
        $name = strtolower($this->extractApplicationName($app));
        return 'https://' . Str::slug($name) . '.example.com';
    }

    /**
     * Estrae il progetto
     */
    private function extractProject(array $app): ?string
    {
        return $app['project'] ?? $app['projectName'] ?? null;
    }

    /**
     * Estrae la categoria
     */
    private function extractCategory(array $app): ?string
    {
        return $app['category'] ?? $app['applicationType'] ?? null;
    }

    /**
     * Estrae l'URL dell'icona
     */
    private function extractIconUrl(array $app): ?string
    {
        return $app['iconUrl'] ?? $app['logoUrl'] ?? $app['imageUrl'] ?? null;
    }

    /**
     * Estrae l'URL della documentazione
     */
    private function extractDocumentationUrl(array $app): ?string
    {
        return $app['documentationUrl'] ?? $app['apiDocumentationUrl'] ?? null;
    }

    /**
     * Verifica se l'applicazione è attiva
     */
    private function isActive(array $app): bool
    {
        return ($app['active'] ?? $app['isActive'] ?? $app['status'] ?? 'active') === 'active';
    }

    /**
     * Verifica se l'applicazione è strategica
     */
    private function isStrategic(array $app): bool
    {
        return $app['strategic'] ?? $app['isStrategic'] ?? false;
    }

    /**
     * Estrae il proprietario scientifico
     */
    private function extractScientificOwner(array $app): ?string
    {
        return $app['scientificOwner'] ?? $app['owner'] ?? null;
    }

    /**
     * Estrae il contatto scientifico
     */
    private function extractScientificContact(array $app): ?string
    {
        return $app['scientificContact'] ?? null;
    }

    /**
     * Estrae l'URL del cockpit
     */
    private function extractCockpitUrl(array $app): ?string
    {
        return $app['cockpitUrl'] ?? $app['adminUrl'] ?? null;
    }

    /**
     * Estrae l'URL della sandbox
     */
    private function extractSandboxUrl(array $app): ?string
    {
        return $app['sandboxUrl'] ?? $app['testUrl'] ?? null;
    }

    /**
     * Estrae il contatto tecnico interno
     */
    private function extractInternalTechnicalContact(array $app): ?string
    {
        return $app['internalTechnicalContact'] ?? $app['technicalContact'] ?? null;
    }

    /**
     * Estrae il contatto tecnico esterno
     */
    private function extractExternalTechnicalContact(array $app): ?string
    {
        return $app['externalTechnicalContact'] ?? null;
    }

    /**
     * Estrae l'email del contatto tecnico esterno
     */
    private function extractExternalTechnicalEmail(array $app): ?string
    {
        return $app['externalTechnicalEmail'] ?? null;
    }

    /**
     * Estrae la data di scadenza del contratto di supporto
     */
    private function extractContractExpiry(array $app): ?string
    {
        $expiry = $app['supportContractExpiry'] ?? $app['contractExpiry'] ?? null;
        
        if ($expiry) {
            try {
                return \Carbon\Carbon::parse($expiry)->format('Y-m-d');
            } catch (\Exception $e) {
                Log::warning("Data di scadenza contratto non valida: {$expiry}");
            }
        }
        
        return null;
    }

    /**
     * Estrae le note sul contratto
     */
    private function extractContractNotes(array $app): ?string
    {
        return $app['contractNotes'] ?? $app['supportNotes'] ?? null;
    }

    /**
     * Ottieni statistiche sul sync delle applicazioni
     */
    public function getSyncStats(): array
    {
        $total = Application::count();
        $active = Application::where('is_active', true)->count();
        $strategic = Application::where('is_strategic', true)->count();
        $withClientId = Application::whereNotNull('client_id')->count();
        
        return [
            'total_applications' => $total,
            'active_applications' => $active,
            'inactive_applications' => $total - $active,
            'strategic_applications' => $strategic,
            'oauth2_applications' => $withClientId,
            'expiring_contracts' => Application::expiringWithin(30)->count(),
            'expired_contracts' => Application::expired()->count(),
        ];
    }

    /**
     * Sincronizza una singola applicazione per ID
     */
    public function syncApplicationById(string $applicationId): ?Application
    {
        try {
            $this->authenticate();
            
            $response = Http::withToken($this->accessToken)
                ->get($this->baseUrl . '/api/server/v1/applications/' . $applicationId);

            if (!$response->successful()) {
                throw new \Exception("Applicazione {$applicationId} non trovata");
            }

            $wso2App = $response->json();
            return $this->createOrUpdateApplication($wso2App);

        } catch (\Exception $e) {
            Log::error("Errore sync applicazione {$applicationId}: " . $e->getMessage());
            throw $e;
        }
    }
}
