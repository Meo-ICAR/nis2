<?php

namespace App\Console\Commands;

use App\Services\FossrService;
use Illuminate\Console\Command;

class FetchFossrProgetti extends Command
{
    /**
     * Il nome e la firma del comando da terminale.
     * @var string
     */
    protected $signature = 'fossr:fetch-progetti';

    /**
     * La descrizione del comando.
     * @var string
     */
    protected $description = 'Autenticazione su FOSSR e recupero della lista progetti';

    /**
     * Esegue il comando.
     */
    public function handle(FossrService $fossrService): int
    {
        $this->info('Inizio procedura di autenticazione...');

        // 1. Tentativo di autenticazione
        if (!$fossrService->authenticate()) {
            $this->error("Errore durante l'autenticazione. Controlla i log o le credenziali.");
            return self::FAILURE;
        }

        $this->info('Autenticazione riuscita! Recupero progetti in corso...');

        // 2. Recupero dei dati
        $progetti = $fossrService->getProgetti();

        if ($progetti === null) {
            $this->error('Errore durante il recupero dei progetti dal gateway.');
            return self::FAILURE;
        }

        // 3. Output dei risultati (formattati in tabella per comodità)
        if (empty($progetti)) {
            $this->warn('Nessun progetto trovato.');
        } else {
            $this->table(
                ['ID', 'Nome Progetto', 'Descrizione'],  // Sostituisci con le chiavi reali del JSON
                $progetti
            );
            $this->success('Operazione completata con successo!');
        }

        return self::SUCCESS;
    }
}
