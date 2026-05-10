<?php

namespace App\Console\Commands;

use App\Services\Wso2Service;
use Illuminate\Console\Command;

class TestWso2 extends Command
{
    protected $signature = 'wso2:test-users';
    protected $description = 'Testa il download degli utenti da WSO2';

    public function handle(Wso2Service $wso2Service)
    {
        $this->info('Chiamata a WSO2 in corso...');

        $results = $wso2Service->getUsersList();

        if (isset($results['Resources'])) {
            $this->info('Successo! Trovati ' . count($results['Resources']) . ' utenti.');

            $headers = ['ID', 'UserName'];
            $data = collect($results['Resources'])->map(function ($user) {
                return [$user['id'], $user['userName']];
            });

            $this->table($headers, $data);
        } else {
            $this->error('Errore durante il recupero.');
            $this->line(json_encode($results, JSON_PRETTY_PRINT));
        }
    }
}
