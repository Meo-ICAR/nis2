<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DellOmeService;
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
    protected $description = 'Esporta l\'inventario asset e garanzie da Dell OME';

    /**
     * Esecuzione del comando.
     */
    public function handle(DellOmeService $dellService)
    {
        $this->info('Inizio recupero dati da Dell OME...');

        $assets = $dellService->getFullAssetExport();

        if ($assets->isEmpty()) {
            $this->error('Nessun dato recuperato. Controlla i log e la connessione all\'appliance.');
            return 1;
        }

        $format = $this->option('format');
        $fileName = 'exports/dell_assets_' . now()->format('Y-m-d_His') . '.' . $format;

        if ($format === 'json') {
            Storage::put($fileName, $assets->toJson(JSON_PRETTY_PRINT));
        } else {
            // Logica semplice per CSV
            $csvContent = "ServiceTag,Modello,IP,Stato,Garanzia_Scadenza\n";
            foreach ($assets as $asset) {
                $csvContent .= "{$asset['service_tag']},{$asset['modello']},{$asset['ip']},{$asset['stato_health']},{$asset['garanzia']['scadenza']}\n";
            }
            Storage::put($fileName, $csvContent);
        }

        $this->table(
            ['Tag', 'Modello', 'IP', 'Scadenza Garanzia'],
            $assets->map(fn($a) => [$a['service_tag'], $a['modello'], $a['ip'], $a['garanzia']['scadenza']])->toArray()
        );

        $this->success("Export completato con successo! File salvato in: storage/app/{$fileName}");
        
        return 0;
    }
}