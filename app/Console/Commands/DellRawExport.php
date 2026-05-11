<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DellOmeSimpleService;

class DellRawExport extends Command
{
    /**
     * Il nome e la firma del comando.
     */
    protected $signature = 'dell:raw-export {--show-devices : Mostra solo dispositivi} {--show-warranties : Mostra solo garanzie}';

    /**
     * La descrizione del comando.
     */
    protected $description = 'Mostra dati raw da Dell OME senza salvare su file';

    /**
     * Esecuzione del comando.
     */
    public function handle(DellOmeSimpleService $dellService)
    {
        $this->info('Recupero dati raw da Dell OME...');

        if ($this->option('show-warranties') || !$this->option('show-devices')) {
            $this->line("\n=== GARANZIE ===");
            $warranties = $dellService->getWarranties();
            
            if ($warranties) {
                $warrantyList = $warranties['value'] ?? [];
                $this->info("Trovate " . count($warrantyList) . " garanzie");
                
                if (count($warrantyList) > 0) {
                    $this->line(json_encode($warranties, JSON_PRETTY_PRINT));
                }
            } else {
                $this->error('Impossibile recuperare le garanzie');
            }
        }

        if ($this->option('show-devices') || !$this->option('show-warranties')) {
            $this->line("\n=== DISPOSITIVI ===");
            $devices = $dellService->getDevices();
            
            if ($devices) {
                $deviceList = $devices['value'] ?? [];
                $this->info("Trovati " . count($deviceList) . " dispositivi");
                $this->line(json_encode($devices, JSON_PRETTY_PRINT));
            } else {
                $this->error('Impossibile recuperare i dispositivi');
            }
        }

        return 0;
    }
}
