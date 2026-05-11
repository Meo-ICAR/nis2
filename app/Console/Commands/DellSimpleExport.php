<?php

namespace App\Console\Commands;

use App\Services\DellOmeSimpleService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DellSimpleExport extends Command
{
    /**
     * Il nome e la firma del comando.
     */
    protected $signature = 'dell:simple-export {--format=json : Il formato del file (json o csv)} {--diagnose : Esegui solo diagnostica connessione}';

    /**
     * La descrizione del comando.
     */
    protected $description = 'Esporta dati da Dell OME utilizzando il servizio semplificato';

    /**
     * Esecuzione del comando.
     */
    public function handle(DellOmeSimpleService $dellService)
    {
        // Se richiesta solo diagnostica
        if ($this->option('diagnose')) {
            $this->info('Esecuzione diagnostica connessione Dell OME...');

            $diagnosis = $dellService->diagnoseConnection();

            $this->newLine();
            $this->info('=== DIAGNOSI CONNESSIONE ===');
            $this->line('Timestamp: ' . $diagnosis['timestamp']);

            $this->newLine();
            $this->info('Configurazione:');
            $this->line('Host: ' . ($diagnosis['config']['host'] ?? 'Non impostato'));
            $this->line('Username: ' . ($diagnosis['config']['username'] ?? 'Non impostato'));
            $this->line('Host configurato: ' . ($diagnosis['config']['host_set'] ? 'SI' : 'NO'));
            $this->line('Username configurato: ' . ($diagnosis['config']['username_set'] ? 'SI' : 'NO'));
            $this->line('Password configurata: ' . ($diagnosis['config']['password_set'] ? 'SI' : 'NO'));

            $this->newLine();
            $this->info('Test:');

            foreach ($diagnosis['tests'] as $testName => $test) {
                $status = $test['status'];
                $statusColor = match ($status) {
                    'PASS' => 'green',
                    'FAIL' => 'red',
                    'ERROR' => 'red',
                    default => 'yellow'
                };

                $this->line("<{$statusColor}>{$testName}: {$status}</{$statusColor}> - {$test['message']}");

                if (isset($test['response_time_ms'])) {
                    $this->line("  Tempo risposta: {$test['response_time_ms']}ms");
                }
            }

            return 0;
        }

        $this->info('Inizio recupero dati da Dell OME (servizio semplificato)...');

        // Recupera dispositivi
        $this->line('Recupero dispositivi...');
        $devices = $dellService->getDevices();

        if (!$devices) {
            $this->error('Impossibile recuperare i dispositivi. Controlla i log per dettagli.');
            return 1;
        }

        $deviceList = $devices['value'] ?? [];
        $this->info('Recuperati ' . count($deviceList) . ' dispositivi');

        // Recupera garanzie
        $this->line('Recupero garanzie...');
        $warranties = $dellService->getWarranties();

        if (!$warranties) {
            $this->warn('Impossibile recuperare le garanzie. Procedo con solo i dispositivi.');
            $warrantyList = [];
        } else {
            $warrantyList = $warranties['value'] ?? [];
            $this->info('Recuperate ' . count($warrantyList) . ' garanzie');
        }

        // Prepara dati per export
        $exportData = collect($deviceList)->map(function ($device) use ($warrantyList) {
            $warranty = collect($warrantyList)->firstWhere('DeviceId', $device['Id']);

            return [
                'id' => $device['Id'] ?? '',
                'device_name' => $device['DeviceName'] ?? 'N/A',
                'service_tag' => $device['ServiceTag'] ?? '',
                'model' => $device['Model'] ?? '',
                'ip_address' => $device['IPAddress'] ?? '',
                'status' => $this->translateStatus($device['Status'] ?? 0),
                'warranty_type' => $warranty['EntitlementType'] ?? 'N/A',
                'warranty_end_date' => $warranty['EndDate'] ?? 'N/A',
                'warranty_days_remaining' => $warranty['DaysRemaining'] ?? 0,
                'warranty_status' => ($warranty['DaysRemaining'] ?? 0) > 0 ? 'Attiva' : 'Scaduta',
            ];
        });

        if ($exportData->isEmpty()) {
            $this->error('Nessun dato da esportare.');
            return 1;
        }

        // Salva su file
        $format = $this->option('format');
        $fileName = 'exports/dell_simple_export_' . now()->format('Y-m-d_His') . '.' . $format;

        if ($format === 'json') {
            Storage::put($fileName, $exportData->toJson(JSON_PRETTY_PRINT));
        } else {
            // CSV
            $csvContent = "ID,Device Name,Service Tag,Model,IP Address,Status,Warranty Type,Warranty End Date,Warranty Days Remaining,Warranty Status\n";
            foreach ($exportData as $item) {
                $csvContent .= implode(',', [
                    $item['id'],
                    $item['device_name'],
                    $item['service_tag'],
                    $item['model'],
                    $item['ip_address'],
                    $item['status'],
                    $item['warranty_type'],
                    $item['warranty_end_date'],
                    $item['warranty_days_remaining'],
                    $item['warranty_status']
                ]) . "\n";
            }
            Storage::put($fileName, $csvContent);
        }

        // Mostra tabella riassuntiva
        $this->newLine();
        $this->table(
            ['Service Tag', 'Device Name', 'Model', 'IP', 'Status', 'Warranty'],
            $exportData->take(10)->map(function ($item) {
                return [
                    $item['service_tag'],
                    $item['device_name'],
                    $item['model'],
                    $item['ip_address'],
                    $item['status'],
                    $item['warranty_status']
                ];
            })->toArray()
        );

        if ($exportData->count() > 10) {
            $this->line('... e altri ' . ($exportData->count() - 10) . ' dispositivi');
        }

        $this->newLine();
        $this->info("✅ Export completato! File salvato in: storage/app/{$fileName}");
        $this->info("Totali: {$exportData->count()} dispositivi esportati");

        return 0;
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
