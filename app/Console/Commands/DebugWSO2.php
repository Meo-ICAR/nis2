<?php

namespace App\Console\Commands;

use App\Services\WSO2DebugService;
use Illuminate\Console\Command;

class DebugWSO2 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wso2:debug 
                            {--save : Salva il report su file}
                            {--full : Mostra output completo (inclusi headers e body)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug completo della connessione WSO2 per identificare problemi';

    /**
     * Execute the console command.
     */
    public function handle(WSO2DebugService $debugService): int
    {
        $this->info('🔍 Inizio debug WSO2...');
        $this->newLine();

        try {
            $debug = $debugService->debugAuthentication();

            // Mostra configurazione
            $this->section('Configurazione WSO2');
            $this->table(
                ['Parametro', 'Valore'],
                [
                    ['Base URL', $debug['config']['base_url']],
                    ['Client ID', $debug['config']['client_id']],
                    ['Client Secret', $debug['config']['client_secret']],
                ]
            );

            // Mostra risultati di ogni step
            foreach ($debug['steps'] as $step) {
                $this->newLine();
                $this->section($step['name']);
                
                $icon = $step['success'] ? '✅' : '❌';
                $this->line("{$icon} {$step['message']}");
                
                if (isset($step['url'])) {
                    $this->line("📍 URL: {$step['url']}");
                    $this->line("🔄 Method: {$step['method']}");
                }

                if ($this->option('full') && isset($step['response'])) {
                    $this->newLine();
                    $this->line('📊 Response Details:');
                    $this->line("   Status: {$step['response']['status']}");
                    $this->line("   Successful: " . ($step['response']['successful'] ? 'Yes' : 'No'));
                    $this->line("   Client Error: " . ($step['response']['client_error'] ? 'Yes' : 'No'));
                    $this->line("   Server Error: " . ($step['response']['server_error'] ? 'Yes' : 'No'));
                    
                    if (!empty($step['response']['body'])) {
                        $this->newLine();
                        $this->line('📄 Response Body:');
                        $body = $step['response']['body'];
                        if (strlen($body) > 500) {
                            $this->line(substr($body, 0, 500) . '...');
                        } else {
                            $this->line($body);
                        }
                    }
                }

                // Mostra info specifiche
                if (isset($step['token_info'])) {
                    $this->newLine();
                    $this->line('🔑 Token Info:');
                    foreach ($step['token_info'] as $key => $value) {
                        $this->line("   {$key}: {$value}");
                    }
                }

                if (isset($step['endpoints'])) {
                    $this->newLine();
                    $this->line('🔗 Available Endpoints:');
                    foreach ($step['endpoints'] as $key => $value) {
                        $this->line("   {$key}: {$value}");
                    }
                }

                if (isset($step['users_count'])) {
                    $this->newLine();
                    $this->line("👥 Users Found: {$step['users_count']}");
                    
                    if (isset($step['users_sample']) && is_array($step['users_sample'])) {
                        $this->line('📋 Sample Users:');
                        foreach ($step['users_sample'] as $i => $user) {
                            $this->line("   User " . ($i + 1) . ": " . json_encode($user, JSON_PRETTY_PRINT));
                        }
                    }
                }

                if (isset($step['error'])) {
                    $this->newLine();
                    $this->error("❌ Error: {$step['error']}");
                }

                if (isset($step['error_details'])) {
                    $this->newLine();
                    $this->line('🚨 Error Details:');
                    $this->line(json_encode($step['error_details'], JSON_PRETTY_PRINT));
                }
            }

            // Mostra errori globali
            if (isset($debug['error'])) {
                $this->newLine();
                $this->section('Errore Globale');
                $this->error("❌ {$debug['error']['message']}");
                $this->error("📁 File: {$debug['error']['file']}:{$debug['error']['line']}");
                
                if ($this->option('full')) {
                    $this->newLine();
                    $this->line('📚 Stack Trace:');
                    $this->line($debug['error']['trace']);
                }
            }

            // Salva su file se richiesto
            if ($this->option('save')) {
                $debugService->logDebug();
                $this->newLine();
                $this->info('💾 Report salvato in storage/logs/');
            }

            $this->newLine();
            if ($debug['success']) {
                $this->info('🎉 Debug completato con successo!');
                return self::SUCCESS;
            } else {
                $this->error('💥 Debug completato con errori!');
                return self::FAILURE;
            }

        } catch (\Exception $e) {
            $this->error('💥 Errore durante il debug: ' . $e->getMessage());
            
            if ($this->option('full')) {
                $this->error('Stack trace: ' . $e->getTraceAsString());
            }

            return self::FAILURE;
        }
    }

    /**
     * Print a section header
     */
    private function section(string $title): void
    {
        $this->newLine();
        $this->line("┌─ " . $title);
        $this->line("│");
    }
}
