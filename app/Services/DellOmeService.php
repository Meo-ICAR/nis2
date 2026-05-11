<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DellOmeService
{
    protected string $baseUrl;
    protected string $user;
    protected string $pass;

    public function __construct()
    {
        $this->baseUrl = "https://" . config('services.dell.ip');
        $this->user = config('services.dell.user');
        $this->pass = config('services.dell.pass');
    }

    /**
     * Gestisce l'autenticazione tramite Session Cookie.
     * Salva il token in cache per 30 minuti.
     */
    private function getSessionToken(): ?string
    {
        return Cache::remember('dell_ome_token', 1800, function () {
            $response = Http::withoutVerifying()
                ->post("{$this->baseUrl}/api/SessionService/Sessions", [
                    'UserName' => $this->user,
                    'Password' => $this->pass,
                    'SessionType' => 'API',
                ]);

            if ($response->successful()) {
                return $response->header('X-Auth-Token');
            }

            Log::error("Dell OME Login fallito: " . $response->body());
            return null;
        });
    }

    /**
     * Esporta l'inventario completo sincronizzato con le garanzie.
     */
    public function getFullAssetExport()
    {
        $token = $this->getSessionToken();

        if (!$token) {
            return collect([]);
        }

        // 1. Recupero Dispositivi (Filtriamo solo i Server per efficienza)
        $devicesReq = Http::withoutVerifying()
            ->withHeaders(['X-Auth-Token' => $token])
            ->get("{$this->baseUrl}/api/DeviceService/Devices", [
                '$filter' => "Type eq 'Server'",
                '$select' => "Id,DeviceName,Model,ServiceTag,IPAddress,Status"
            ]);

        // 2. Recupero Garanzie (Endpoint AssetAdvisor)
        $warrantyReq = Http::withoutVerifying()
            ->withHeaders(['X-Auth-Token' => $token])
            ->get("{$this->baseUrl}/api/AssetAdvisorService/Warranties");

        if (!$devicesReq->successful()) {
            return collect([]);
        }

        $devices = $devicesReq->json()['value'] ?? [];
        $warranties = collect($warrantyReq->json()['value'] ?? []);

        return collect($devices)->map(function ($device) use ($warranties) {
            // Cerchiamo la garanzia corrispondente tramite l'ID del dispositivo
            $w = $warranties->firstWhere('DeviceId', $device['Id']);

            return [
                'id_interno'    => $device['Id'],
                'asset_name'    => $device['DeviceName'] ?? 'N/A',
                'service_tag'   => $device['ServiceTag'],
                'modello'       => $device['Model'],
                'ip'            => $device['IPAddress'],
                'stato_health'  => $this->translateStatus($device['Status']),
                'garanzia'      => [
                    'tipo'       => $w['EntitlementType'] ?? 'Unknown',
                    'scadenza'   => isset($w['EndDate']) ? Carbon::parse($w['EndDate'])->format('Y-m-d') : 'N/A',
                    'giorni_res' => $w['DaysRemaining'] ?? 0,
                    'stato'      => ($w['DaysRemaining'] ?? 0) > 0 ? 'Attiva' : 'Scaduta',
                ]
            ];
        });
    }

    /**
     * Converte i codici di stato numerici di Dell in stringhe leggibili.
     */
    private function translateStatus(int $status): string
    {
        return match ($status) {
            1000 => 'OK',
            2000 => 'Unknown',
            3000 => 'Warning',
            4000 => 'Critical',
            default => 'Non gestito',
        };
    }
}