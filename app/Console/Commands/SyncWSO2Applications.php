<?php

namespace App\Console\Commands;

use App\Services\WSO2ApplicationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncWSO2Applications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wso2:sync-applications 
                            {--force : Forza il sync anche se già eseguito di recente}
                            {--dry-run : Mostra cosa verrebbe sincronizzato senza eseguire operazioni}
                            {--id= : Sincronizza solo una specifica applicazione per ID}
                            {--category= : Filtra applicazioni per categoria}
                            {--active-only : Sincronizza solo applicazioni attive}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincronizza le applicazioni da WSO2 al database locale';

    /**
     * Execute the console command.
     */
    public function handle(WSO2ApplicationService $wso2Service): int
    {
        $this->info('Inizio sincronizzazione applicazioni da WSO2...');

        try {
            // Mostra statistiche attuali
            $stats = $wso2Service->getSyncStats();
            $this->table(
                ['Tipo', 'Count'],
                [
                    ['Total Applications', $stats['total_applications']],
                    ['Active Applications', $stats['active_applications']],
                    ['Strategic Applications', $stats['strategic_applications']],
                    ['OAuth2 Applications', $stats['oauth2_applications']],
                    ['Expiring Contracts', $stats['expiring_contracts']],
                    ['Expired Contracts', $stats['expired_contracts']],
                ]
            );

            if ($this->option('dry-run')) {
                $this->warn('Modalità DRY RUN - Nessuna modifica sarà applicata');
                return self::SUCCESS;
            }

            // Sincronizza applicazione specifica o tutte
            if ($appId = $this->option('id')) {
                $this->info("Sincronizzazione applicazione specifica: {$appId}");
                $application = $wso2Service->syncApplicationById($appId);
                
                if ($application) {
                    $this->info("✅ Applicazione sincronizzata: {$application->name}");
                    $this->showApplicationDetails($application);
                } else {
                    $this->error("❌ Applicazione non trovata");
                    return self::FAILURE;
                }
            } else {
                // Esegui il sync completo
                $this->info('Connessione a WSO2 e download applicazioni...');
                $result = $wso2Service->syncApplications();

                // Mostra risultati
                $this->info('✅ Sincronizzazione completata con successo!');
                $this->table(
                    ['Azione', 'Count'],
                    [
                        ['Applicazioni sincronizzate', $result['total']],
                        ['Nuove applicazioni create', $result['created']],
                        ['Applicazioni aggiornate', $result['updated']],
                        ['Applicazioni saltate', $result['skipped']],
                    ]
                );

                // Mostra dettagli applicazioni
                if ($result['total'] > 0 && $this->option('verbose')) {
                    $this->showApplicationsList($result['synced']);
                }
            }

            Log::info("Command wso2:sync-applications executed successfully");

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Errore durante la sincronizzazione: ' . $e->getMessage());
            
            if ($this->option('verbose')) {
                $this->error('Stack trace: ' . $e->getTraceAsString());
            }

            Log::error("Command wso2:sync-applications failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return self::FAILURE;
        }
    }

    /**
     * Mostra i dettagli di un'applicazione
     */
    private function showApplicationDetails($application): void
    {
        $this->table(
            ['Campo', 'Valore'],
            [
                ['ID', $application->id],
                ['Nome', $application->name],
                ['Short Name', $application->short_name],
                ['URL', $application->url],
                ['Client ID', $application->client_id],
                ['Attiva', $application->is_active ? 'Sì' : 'No'],
                ['Strategica', $application->is_strategic ? 'Sì' : 'No'],
                ['Categoria', $application->category],
                ['Proprietario Scientifico', $application->scientific_owner],
                ['Contatto Tecnico', $application->internal_technical_contact],
                ['Scadenza Contratto', $application->support_contract_expiry?->format('d/m/Y')],
                ['Creata', $application->created_at->format('d/m/Y H:i')],
                ['Aggiornata', $application->updated_at->format('d/m/Y H:i')],
            ]
        );
    }

    /**
     * Mostra la lista delle applicazioni sincronizzate
     */
    private function showApplicationsList(array $applications): void
    {
        $this->newLine();
        $this->info('Dettagli applicazioni sincronizzate:');
        
        $appsData = [];
        foreach ($applications as $app) {
            $appsData[] = [
                $app->id,
                $app->name,
                $app->short_name,
                $app->category,
                $app->is_active ? 'Sì' : 'No',
                $app->is_strategic ? 'Sì' : 'No',
                $app->client_id ? 'Sì' : 'No',
                $app->support_contract_expiry?->format('d/m/Y'),
            ];
        }

        $this->table(
            ['ID', 'Nome', 'Short Name', 'Categoria', 'Attiva', 'Strategica', 'OAuth2', 'Scadenza'],
            $appsData
        );
    }
}
