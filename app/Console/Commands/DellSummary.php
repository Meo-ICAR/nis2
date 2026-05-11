<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DellOmeSimpleService;

class DellSummary extends Command
{
    /**
     * Il nome e la firma del comando.
     */
    protected $signature = 'dell:summary';

    /**
     * La descrizione del comando.
     */
    protected $description = 'Mostra riassunto dettagliato dei dati Dell OME';

    /**
     * Esecuzione del comando.
     */
    public function handle(DellOmeSimpleService $dellService)
    {
        $this->info('=== RIASSUNTO DATI DELL OME ===');

        // Recupera dispositivi
        $devices = $dellService->getDevices();
        if (!$devices) {
            $this->error('Impossibile recuperare i dispositivi');
            return 1;
        }

        $deviceList = $devices['value'] ?? [];
        $totalDevices = $devices['@odata.count'] ?? count($deviceList);

        $this->newLine();
        $this->info("📊 STATISTICHE GENERALI");
        $this->line("Dispositivi totali nel sistema: {$totalDevices}");
        $this->line("Dispositivi recuperati (pagina corrente): " . count($deviceList));
        $this->line("Ha pagine successive: " . (isset($devices['@odata.nextLink']) ? 'SÌ' : 'NO'));

        // Analisi dispositivi
        $models = [];
        $statuses = [];
        $powerStates = [];
        $connectionStates = ['connessi' => 0, 'disconnessi' => 0];

        foreach ($deviceList as $device) {
            // Modelli
            $model = $device['Model'] ?? 'Sconosciuto';
            $models[$model] = ($models[$model] ?? 0) + 1;

            // Status
            $status = $device['Status'] ?? 0;
            $statusText = $this->translateStatus($status);
            $statuses[$statusText] = ($statuses[$statusText] ?? 0) + 1;

            // Power State
            $powerState = $device['PowerState'] ?? 0;
            $powerStates[$powerState] = ($powerStates[$powerState] ?? 0) + 1;

            // Connection State
            if ($device['ConnectionState'] ?? false) {
                $connectionStates['connessi']++;
            } else {
                $connectionStates['disconnessi']++;
            }
        }

        $this->newLine();
        $this->info("🖥️ MODELLI DISPOSITIVI");
        foreach ($models as $model => $count) {
            $this->line("  {$model}: {$count}");
        }

        $this->newLine();
        $this->info("📊 STATI SALUTE");
        foreach ($statuses as $status => $count) {
            $color = match($status) {
                'OK' => 'green',
                'Warning' => 'yellow',
                'Critical' => 'red',
                default => 'gray'
            };
            $this->line("  <{$color}>{$status}</{$color}>: {$count}");
        }

        $this->newLine();
        $this->info("⚡ STATI ALIMENTAZIONE");
        foreach ($powerStates as $state => $count) {
            $stateText = $this->translatePowerState($state);
            $this->line("  {$stateText}: {$count}");
        }

        $this->newLine();
        $this->info("🔌 STATI CONNESSIONE");
        $this->line("  Connessi: {$connectionStates['connessi']}");
        $this->line("  Disconnessi: {$connectionStates['disconnessi']}");

        // Dettaglio dispositivi
        $this->newLine();
        $this->info("📋 DETTAGLIO DISPOSITIVI (primi 10)");
        
        $tableData = [];
        foreach (array_slice($deviceList, 0, 10) as $device) {
            $ip = 'N/A';
            if (isset($device['DeviceManagement'][0]['NetworkAddress'])) {
                $ip = $device['DeviceManagement'][0]['NetworkAddress'];
            }

            $tableData[] = [
                $device['DeviceName'] ?? 'N/A',
                $device['DeviceServiceTag'] ?? 'N/A',
                $device['Model'] ?? 'N/A',
                $ip,
                $this->translateStatus($device['Status'] ?? 0),
                $device['ConnectionState'] ? 'SÌ' : 'NO'
            ];
        }

        $this->table(
            ['Nome', 'Service Tag', 'Modello', 'IP', 'Stato', 'Connesso'],
            $tableData
        );

        // Garanzie
        $this->newLine();
        $this->info("🛡️ GARANZIE");
        $warranties = $dellService->getWarranties();
        if ($warranties) {
            $warrantyList = $warranties['value'] ?? [];
            $this->info("Garanzie trovate: " . count($warrantyList));
            
            if (count($warrantyList) > 0) {
                $activeWarranties = 0;
                $expiredWarranties = 0;
                
                foreach ($warrantyList as $warranty) {
                    if (($warranty['DaysRemaining'] ?? 0) > 0) {
                        $activeWarranties++;
                    } else {
                        $expiredWarranties++;
                    }
                }
                
                $this->line("  Garanzie attive: {$activeWarranties}");
                $this->line("  Garanzie scadute: {$expiredWarranties}");
            }
        } else {
            $this->warn("Impossibile recuperare le garanzie (licenza mancante?)");
        }

        return 0;
    }

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

    private function translatePowerState(int $state): string
    {
        return match ($state) {
            1 => 'Powered Off',
            2 => 'Powered On',
            8 => 'Powering On',
            9 => 'Powering Off',
            17 => 'Unknown',
            default => "Stato {$state}",
        };
    }
}
