<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DellOmeService
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;

    public function __construct()
    {
        $this->baseUrl = 'https://' . config('dell.ip', env('DELL_OME_IP')) . '/api';
        $this->username = config('dell.user', env('DELL_OME_USER'));
        $this->password = config('dell.pass', env('DELL_OME_PASS'));
    }

    /**
     * Recupera i dati automatizzando la ricerca del Report ID corretto.
     */
    public function getInventoryWithWarranty()
    {
        $token = $this->generateSessionToken();

        if (!$token) {
            return ['error' => 'Autenticazione fallita su Dell OME'];
        }

        // 1. Determina dinamicamente l'ID del report "Warranty"
        $reportId = $this->findWarrantyReportId($token);

        if (!$reportId) {
            return ['error' => 'Report "Warranty" non trovato nel sistema'];
        }

        // 2. Esegue il report trovato per ottenere Service Tag e IP popolati
        $response = Http::withOptions(['verify' => false])
            ->withHeaders(['X-Auth-Token' => $token])
            ->get("{$this->baseUrl}/ReportService/ReportDefinitions({$reportId})/Filters(0)/Execution");

        if ($response->failed()) {
            return ['error' => "Errore durante l'esecuzione del report"];
        }

        return $this->parseReportResults($response->json());
    }

    /**
     * Autenticazione SessionService per l'utente locale.
     */
    protected function generateSessionToken(): ?string
    {
        $response = Http::withOptions(['verify' => false])
            ->post("{$this->baseUrl}/SessionService/Sessions", [
                'UserName' => $this->username,
                'Password' => $this->password,
                'SessionType' => 'API'
            ]);

        return $response->successful() ? $response->header('X-Auth-Token') : null;
    }

    /**
     * Scarica l'elenco dei report e trova quello di tipo "Warranty" o Device Overview.
     */
    protected function findWarrantyReportId(string $token): ?int
    {
        $response = Http::withOptions(['verify' => false])
            ->withHeaders(['X-Auth-Token' => $token])
            ->get("{$this->baseUrl}/ReportService/ReportDefinitions");

        if ($response->successful()) {
            $reports = $response->json()['value'] ?? [];

            Log::info('Dell OME - Report disponibili', [
                'total_reports' => count($reports),
                'reports' => array_map(fn($r) => $r['Name'], $reports)
            ]);

            // Cerchiamo prima il report Warranty
            foreach ($reports as $report) {
                if (stripos($report['Name'], 'Warranty') !== false) {
                    Log::info("Dell OME - Trovato report Warranty: {$report['Name']} (ID: {$report['Id']})");
                    return $report['Id'];
                }
            }

            // Se non troviamo Warranty, cerchiamo Device Overview o simili
            foreach ($reports as $report) {
                if (stripos($report['Name'], 'Device') !== false && stripos($report['Name'], 'Overview') !== false) {
                    Log::info("Dell OME - Trovato report Device Overview: {$report['Name']} (ID: {$report['Id']})");
                    return $report['Id'];
                }
            }

            // Ultimo tentativo: qualsiasi report con "Device"
            foreach ($reports as $report) {
                if (stripos($report['Name'], 'Device') !== false) {
                    Log::info("Dell OME - Trovato report Device generico: {$report['Name']} (ID: {$report['Id']})");
                    return $report['Id'];
                }
            }
        }

        Log::error('Dell OME - Nessun report trovato', [
            'response_status' => $response->status(),
            'response_body' => $response->body()
        ]);

        return null;
    }

    /**
     * Mappa i risultati risolvendo i campi Service Tag e IP.
     */
    protected function parseReportResults(array $data): array
    {
        return collect($data['Records'] ?? [])->map(function ($row) {
            return [
                'device_name' => $row['Device Name'] ?? 'N/A',
                // Identifier è il campo tecnico per il Service Tag in OME
                'service_tag' => $row['Service Tag'] ?? $row['Identifier'] ?? 'N/A',
                'model' => $row['Device Model'] ?? 'N/A',
                'ip_address' => $row['IP Address'] ?? $row['NetAddress'] ?? 'N/A',
                'warranty_status' => $row['Warranty State'] ?? 'N/A',
                'days_remaining' => $row['Days Remaining'] ?? 0,
            ];
        })->toArray();
    }
}
