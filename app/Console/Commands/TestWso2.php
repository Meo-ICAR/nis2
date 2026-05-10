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

            $headers = ['ID', 'UserName', 'Email'];

            $data = collect($results['Resources'])->map(function ($user) {
                // Estraiamo l'email dall'array SCIM
                $email = 'N/A';
                if (!empty($user['emails'])) {
                    // Prende la prima email disponibile
                    $email = is_array($user['emails'][0]) ? $user['emails'][0]['value'] : $user['emails'][0];
                }

                return [
                    $user['id'],
                    $user['userName'],
                    $email
                ];
            });

            $this->table($headers, $data);
        } else {
            $this->error('Nessuna risorsa trovata.');
            $this->line(json_encode($results, JSON_PRETTY_PRINT));
        }
    }
}
