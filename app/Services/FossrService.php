<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FossrService
{
    /**
     * Recupera la lista dei progetti, arricchisce ognuno con la propria vm-list
     * e salva il risultato finale in un file all'interno dello storage di Laravel.
     *
     * @return array
     */
    public function getProjectsWithVmsAndSave(): array
    {
        // 1. Richiesta iniziale dei Token (Password Grant)
        $authUrl = config('services.fossr.auth_url');

        $tokenPayload = [
            'grant_type' => 'password',
            'username' => config('services.fossr.username'),
            'password' => config('services.fossr.password'),
            'scope' => 'openid email profile roles',
        ];

        $tokenResponse = Http::asForm()
            ->withOptions(['verify' => false])
            ->withBasicAuth(config('services.fossr.client_id'), config('services.fossr.client_secret'))
            ->post($authUrl, $tokenPayload);

        $idToken = $tokenResponse->json('id_token');

        if (!$idToken) {
            Log::error("FossrService: Impossibile estrarre l'id_token dallo Step di Auth");
            dd([
                'ERRORE' => "Impossibile estrarre l'id_token dallo Step di Auth",
                'RISPOSTA_SERVER' => $tokenResponse->json() ?? $tokenResponse->body()
            ]);
        }

        // 2. Recupero della lista dei progetti principali
        $gatewayUrl = rtrim(config('services.fossr.gateway_url'), '/');
        $projectsUrl = $gatewayUrl . '/progetti';

        $projectsResponse = Http::withOptions(['verify' => false])
            ->withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $idToken,
                'WSO2-Authorization' => trim(config('services.fossr.api_key')),
            ])
            ->get($projectsUrl);

        if ($projectsResponse->failed()) {
            Log::error('FossrService: Fallito recupero progetti dal gateway');
            dd([
                'ERRORE' => 'Fallito recupero progetti',
                'STATUS' => $projectsResponse->status(),
                'BODY' => $projectsResponse->json() ?? $projectsResponse->body()
            ]);
        }

        $progetti = $projectsResponse->json();

        // 3. Ciclo su ogni progetto per recuperare la lista delle VM
        if (is_array($progetti)) {
            foreach ($progetti as $key => $progetto) {
                if (isset($progetto['id'])) {
                    $vmList = $this->getVmListByProjectId($progetto['id'], $idToken, $gatewayUrl);
                    $progetti[$key]['vm_list'] = $vmList;
                }
            }
        }

        // 4. Generazione del Payload Strutturato da salvare in Storage
        $outputData = [
            'dispatched_at' => now()->toIso8601String(),
            'total_projects' => is_array($progetti) ? count($progetti) : 0,
            'projects' => $progetti
        ];

        // Definizione dei nomi file
        $timestampName = 'fossr_projects_vms_' . now()->format('Ymd_His') . '.json';
        $latestName = 'fossr_projects_vms_latest.json';

        $jsonPayload = json_encode($outputData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // Salvataggio effettivo nel disco locale di Laravel (storage/app/)
        Storage::disk('local')->put($timestampName, $jsonPayload);
        Storage::disk('local')->put($latestName, $jsonPayload);

        Log::info("FossrService: Dati salvati con successo in storage: {$timestampName}");

        return $progetti;
    }

    /**
     * Interroga l'endpoint iaas/<id>/vm-list di un singolo progetto.
     *
     * @param mixed $projectId
     * @param string $idToken
     * @param string $gatewayUrl
     * @return array
     */
    public function getVmListByProjectId($projectId, string $idToken, string $gatewayUrl): array
    {
        $url = $gatewayUrl . '/servizi/iaas/' . $projectId . '/vm-list';

        $response = Http::withOptions(['verify' => false])
            ->withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $idToken,
                'WSO2-Authorization' => trim(config('services.fossr.api_key')),
            ])
            ->get($url);

        if ($response->failed()) {
            return [
                'error' => true,
                'status' => $response->status(),
                'message' => 'Impossibile recuperare le VM per questo progetto.'
            ];
        }

        return $response->json() ?? [];
    }
}
