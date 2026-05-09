<?php

namespace App\Console\Commands;

use App\Services\WSO2UserService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncWSO2AdminUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wso2:sync-admin-users 
                            {--force : Forza il sync anche se già eseguito di recente}
                            {--dry-run : Mostra cosa verrebbe sincronizzato senza eseguire operazioni}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincronizza gli utenti amministratori da WSO2 al database locale';

    /**
     * Execute the console command.
     */
    public function handle(WSO2UserService $wso2Service): int
    {
        $this->info('Inizio sincronizzazione utenti amministratori da WSO2...');

        try {
            // Mostra statistiche attuali
            $stats = $wso2Service->getSyncStats();
            $this->table(
                ['Tipo', 'Count'],
                [
                    ['Total Admins', $stats['total_admins']],
                    ['Active Admins', $stats['active_admins']],
                    ['Inactive Admins', $stats['inactive_admins']],
                ]
            );

            if ($this->option('dry-run')) {
                $this->warn('Modalità DRY RUN - Nessuna modifica sarà applicata');
                return self::SUCCESS;
            }

            // Esegui il sync
            $this->info('Connessione a WSO2 e download utenti...');
            $result = $wso2Service->syncAdminUsers();

            // Mostra risultati
            $this->info('✅ Sincronizzazione completata con successo!');
            $this->table(
                ['Azione', 'Count'],
                [
                    ['Utenti sincronizzati', $result['total']],
                    ['Nuovi utenti creati', $result['created']],
                    ['Utenti aggiornati', $result['updated']],
                ]
            );

            // Mostra dettagli utenti
            if ($result['total'] > 0 && $this->option('verbose')) {
                $this->newLine();
                $this->info('Dettagli utenti sincronizzati:');
                
                $usersData = [];
                foreach ($result['synced'] as $user) {
                    $usersData[] = [
                        $user->id,
                        $user->name,
                        $user->email,
                        $user->is_admin ? 'Sì' : 'No',
                        $user->is_active ? 'Sì' : 'No',
                        $user->created_at->format('d/m/Y H:i'),
                    ];
                }

                $this->table(
                    ['ID', 'Nome', 'Email', 'Admin', 'Attivo', 'Creato'],
                    $usersData
                );
            }

            Log::info("Command wso2:sync-admin-users executed successfully", [
                'created' => $result['created'],
                'updated' => $result['updated'],
                'total' => $result['total']
            ]);

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Errore durante la sincronizzazione: ' . $e->getMessage());
            
            if ($this->option('verbose')) {
                $this->error('Stack trace: ' . $e->getTraceAsString());
            }

            Log::error("Command wso2:sync-admin-users failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return self::FAILURE;
        }
    }
}
