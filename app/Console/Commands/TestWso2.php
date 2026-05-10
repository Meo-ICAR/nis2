<?php

namespace App\Console\Commands;

use App\Services\Wso2Service;
use Illuminate\Console\Command;

class TestWso2 extends Command
{
    protected $signature = 'wso2:test-users';
    protected $description = 'Testa il download degli utenti da WSO2';

    // In app/Console/Commands/TestWso2.php

    public function handle(Wso2Service $wso2Service)
    {
        $this->info('Recupero Applicazioni (Filtro localhost attivo)...');

        $apps = $wso2Service->getApplicationsWithUrls();

        if (empty($apps)) {
            $this->warn('Nessuna applicazione con URL di produzione trovata.');
            return;
        }

        $headers = ['ID', 'Nome Applicazione', 'URL di Produzione'];
        $data = collect($apps)->map(function ($app) {
            return [
                $app['id'],
                $app['name'],
                implode("\n", $app['urls'])
            ];
        });

        $this->table($headers, $data);
    }cle
}
