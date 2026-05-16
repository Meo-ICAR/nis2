<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class FossrService
{
    /**
     * Recupera la lista dei progetti e arricchisce ognuno con la propria vm-list.
     *
     * @return array
     */
    public function getProjects(): array
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
            dd([
                'ERRORE' => 'Fallito recupero progetti',
                'STATUS' => $projectsResponse->status(),
                'BODY' => $projectsResponse->json() ?? $projectsResponse->body()
            ]);
        }

        $progetti = $projectsResponse->json();

        // 3. Ciclo su ogni progetto per recuperare la lista delle VM usando la nuova funzione
        // Assumiamo che l'array contenga i progetti e che l'ID sia sotto la chiave 'id' (es. $progetto['id'])
        if (is_array($progetti)) {
            foreach ($progetti as $key => $progetto) {
                if (isset($progetto['id'])) {
                    // Chiamiamo la nuova funzione dedicata passando l'ID, l'id_token e il gatewayUrl
                    $vmList = $this->getVmListByProjectId($progetto['id'], $idToken, $gatewayUrl);

                    // Iniettiamo il risultato direttamente dentro l'oggetto del progetto corrente
                    $progetti[$key]['vm_list'] = $vmList;
                }
            }
        }

        return $progetti;
    }

    /**
     * Nuova funzione dedicata per interrogare l'endpoint iaas/<id>/vm-list di un singolo progetto.
     * Mantiene gli stessi header di sicurezza scoperti su Postman.
     *
     * @param mixed $projectId
     * @param string $idToken
     * @param string $gatewayUrl
     * @return array
     */
    public function getVmListByProjectId($projectId, string $idToken, string $gatewayUrl): array
    {
        // Costruzione dell'URL dinamico iniettando l'ID del progetto corrente
        $url = $gatewayUrl . '/servizi/iaas/' . $projectId . '/vm-list';

        $response = Http::withOptions(['verify' => false])
            ->withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $idToken,
                'WSO2-Authorization' => trim(config('services.fossr.api_key')),
            ])
            ->get($url);

        // Se la chiamata fallisce per un singolo progetto (es. 404 o nessun servizio IaaS attivo),
        // restituiamo un array vuoto o logghiamo l'errore senza bloccare l'intero ciclo degli altri progetti
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
