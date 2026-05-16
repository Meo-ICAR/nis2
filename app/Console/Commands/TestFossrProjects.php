<?php

namespace App\Console\Commands;

use App\Services\FossrService;
use Illuminate\Console\Command;

class TestFossrProjects extends Command
{
    /**
     * Il nome e la firma del comando da terminale.
     */
    protected $signature = 'fossr:projects';

    /**
     * La descrizione del comando.
     */
    protected $description = 'Testa il FossrService e recupera la lista dei progetti';

    /**
     * Esegue il comando.
     */
    public function handle(FossrService $fossrService)
    {
        $this->info('Inizio scaricamento dati e sincronizzazione VM...');

        $fossrService->getProjectsWithVmsAndSave();

        $this->info('Flusso completato con successo! I dati sono stati archiviati in storage/app/.');

        if (isset($result['error'])) {
            $this->error('Si è verificato un errore: ' . $result['error']);
            $this->error(print_r($result['details'] ?? '', true));
            return Command::FAILURE;
        }

        $this->info('Risposta ricevuta con successo!');

        // Stampa a schermo i risultati formattati (dump and die)

        return Command::SUCCESS;
    }
}
