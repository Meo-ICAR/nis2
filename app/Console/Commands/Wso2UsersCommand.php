<?php

namespace App\Console\Commands;

use App\Services\Wso2UserService;
use Illuminate\Console\Command;

class Wso2UsersCommand extends Command
{
    /**
     * Il nome e la firma del comando.
     */
    protected $signature = 'wso2:users';

    /**
     * La descrizione del comando.
     */
    protected $description = 'Visualizza la lista degli utenti registrati su WSO2 con le relative Email';

    /**
     * Esecuzione del comando.
     */
    public function handle(Wso2UserService $userService)
    {
        $this->info('Recupero della lista utenti in corso...');

        $users = $userService->getUsersList();

        if (isset($users['error'])) {
            $this->error('Errore: ' . $users['error']);
            if (isset($users['details'])) {
                $this->line(json_encode($users['details'], JSON_PRETTY_PRINT));
            }
            return 1;
        }

        if (empty($users)) {
            $this->warn('Nessun utente trovato.');
            return 0;
        }

        // Visualizzazione in tabella
        $this->table(
            ['ID WSO2', 'Username', 'Indirizzo Email'],
            $users
        );

        $this->info("\nTotale utenti trovati: " . count($users));

        return 0;
    }
}
