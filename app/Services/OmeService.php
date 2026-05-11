<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OmeService
{
    protected string $host;
    protected string $username;
    protected string $password;

    public function __construct()
    {
        $this->host = rtrim(config('services.ome.host'), '/');
        $this->username = config('services.ome.username');
        $this->password = config('services.ome.password');
    }

    /**
     * Esegue l'estrazione completa dei dati unificando Dispositivi e Garanzie.
     */
    public function getExportData()
    {
        $token = $this->authenticate();

        if (!$token) {
            return ['error' => 'Autenticazione fallita'];
        }

        // Recuperiamo il Report "Device Overview" o "Warranty" tramite ID.
        // Nota: L'ID 100 è un esempio, va verificato con /api/ReportService/ReportDefinitions
        $reportId = 100;

        $response = Http::withOptions(['verify' => false])
            ->withHeaders(['X-Auth-Token' => $token])
            ->get("{$this->host}/api/ReportService/ReportDefinitions({$reportId})/Filters(0)/Execution");

        if ($response->failed()) {
            return ['error' => 'Impossibile recuperare il report'];
        }

        return $this->formatReportData($response->json());
    }

    /**
     * Gestisce l'autenticazione locale (SessionService).
     */
    protected function authenticate(): ?string
    {
        $response = Http::withOptions(['verify' => false])
            ->post("{$this->host}/api/SessionService/Sessions", [
                'UserName' => $this->username,
                'Password' => $this->password,
                'SessionType' => 'API'
            ]);

        if ($response->successful()) {
            // Il token è fondamentale per le chiamate successive
            return $response->header('X-Auth-Token');
        }

        Log::error('Errore login OME: ' . $response->body());
        return null;
    }

    /**
     * Mappa i dati del report per risolvere i campi vuoti (Service Tag/IP).
     */
    protected function formatReportData(array $data): array
    {
        // I report di OME restituiscono righe che mappano esattamente il file CSV
        return collect($data['Records'] ?? [])->map(function ($record) {
            return [
                'device_name' => $record['Device Name'] ?? 'N/A',
                'service_tag' => $record['Service Tag'] ?? $record['Identifier'] ?? 'N/A',  // Identifier è il tag tecnico
                'model' => $record['Device Model'] ?? 'N/A',
                'ip_address' => $record['IP Address'] ?? 'N/A',
                'warranty' => [
                    'state' => $record['Warranty State'] ?? 'N/A',
                    'days_remaining' => $record['Days Remaining'] ?? 0,
                ]
            ];
        })->toArray();
    }
}
