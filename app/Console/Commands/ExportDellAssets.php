<?php

namespace App\Console\Commands;

use App\Services\DellOmeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ExportDellAssets extends Command
{
    /**
     * Il nome e la firma del comando.
     */
    protected $signature = 'dell:export-assets {--format=json : Il formato del file (json o csv)}';

    /**
     * La descrizione del comando.
     */
    protected $description = "Esporta l'inventario asset e garanzie da Dell OME";

    /**
     * Esecuzione del comando.
     */
    public function handle(DellOmeService $dellService)
    {
        $this->info('Inizio recupero dati da Dell OME (Report Service)...');

        $result = $dellService->getInventoryWithWarranty();

        if (isset($result['error'])) {
            $this->error('Errore: ' . $result['error']);
            return 1;
        }

        $assets = collect($result);

        if ($assets->isEmpty()) {
            $this->error("Nessun dato recuperato. Controlla i log e la connessione all'appliance.");
            return 1;
        }

        $format = $this->option('format');
        $fileName = 'exports/dell_assets_' . now()->format('Y-m-d_His') . '.' . $format;

        if ($format === 'json') {
            Storage::put($fileName, $assets->toJson(JSON_PRETTY_PRINT));
        } else {
            // Logica per CSV con nuovi campi
            $csvContent = "Device Name,Service Tag,Model,IP Address,Warranty Status,Days Remaining\n";
            foreach ($assets as $asset) {
                $csvContent .= implode(',', [
                    $asset['device_name'],
                    $asset['service_tag'],
                    $asset['model'],
                    $asset['ip_address'],
                    $asset['warranty_status'],
                    $asset['days_remaining']
                ]) . "\n";
            }
            Storage::put($fileName, $csvContent);
        }

        $this->table(
            ['Device Name', 'Service Tag', 'Model', 'IP', 'Warranty Status', 'Days Remaining'],
            $assets->map(fn($a) => [
                $a['device_name'],
                $a['service_tag'],
                $a['model'],
                $a['ip_address'],
                $a['warranty_status'],
                $a['days_remaining']
            ])->toArray()
        );

        $this->info("✅ Export completato con successo! File salvato in: storage/app/{$fileName}");
        $this->info("Totali: {$assets->count()} dispositivi esportati");

        return 0;
    }
}
