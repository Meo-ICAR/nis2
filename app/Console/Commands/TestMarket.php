<?php

namespace App\Console\Commands;

use App\Services\MarketService;
use Illuminate\Console\Command;

class TestMarket extends Command
{
    /**
     * Il nome e la firma del comando da terminale.
     */
    protected $signature = 'market:users';

    /**
     * La descrizione del comando.
     */
    protected $description = 'Testa il MarketService e recupera la lista dei progetti';

    /**
     * Esegue il comando.
     */
    public function handle(MarketService $MarketService)
    {
        $this->info('Inizio scaricamento dati e sincronizzazione ...');

        $users = $MarketService->getusers();

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
