<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Jobs\SyncWSO2AdminUsersJob;
use App\Services\WSO2DebugService;
use App\Services\WSO2UserService;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Checkbox;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use Illuminate\Support\Facades\Auth;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Actions\Action::make('sync_wso2_users')
                ->label('Sync Utenti WSO2')
                ->icon('fas-users')
                ->color('warning')
                ->form([
                    Checkbox::make('async')
                        ->label('Esegui in background (consigliato)')
                        ->default(true)
                        ->helperText("Se spuntato, il sync verrà eseguito in background senza bloccare l'interfaccia."),
                ])
                ->action(function (array $data, WSO2UserService $wso2Service) {
                    try {
                        if ($data['async']) {
                            // Esegui in background
                            SyncWSO2AdminUsersJob::dispatch();

                            Notification::make()
                                ->title('Sync Avviato')
                                ->body('La sincronizzazione degli utenti WSO2 è stata avviata in background.')
                                ->success()
                                ->send();
                        } else {
                            // Esegui immediatamente
                            $result = $wso2Service->syncAdminUsers();

                            Notification::make()
                                ->title('Sync Completato')
                                ->body("Sincronizzazione completata: {$result['created']} nuovi, {$result['updated']} aggiornati.")
                                ->success()
                                ->send();
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Errore Sync')
                            ->body('Errore durante la sincronizzazione: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->modalHeading('Sincronizza Utenti da WSO2')
                ->modalDescription('Questo processo importerà gli utenti amministratori da WSO2 e li sincronizzerà con il database locale.')
                ->modalIcon('fas-users')
                ->modalSubmitActionLabel('Avvia Sync'),
            Actions\Action::make('debug_wso2')
                ->label('Debug WSO2')
                ->icon('fas-bug')
                ->color('danger')
                ->action(function (WSO2DebugService $debugService) {
                    try {
                        $debug = $debugService->debugAuthentication();

                        // Crea un report leggibile
                        $report = '🔍 Debug WSO2 Report - ' . now()->format('d/m/Y H:i:s') . "\n\n";

                        foreach ($debug['steps'] as $step) {
                            $icon = $step['success'] ? '✅' : '❌';
                            $report .= "{$icon} {$step['name']}: {$step['message']}\n";

                            if (isset($step['response']['status'])) {
                                $report .= "   Status: {$step['response']['status']}\n";
                            }

                            if (isset($step['error'])) {
                                $report .= "   Error: {$step['error']}\n";
                            }

                            $report .= "\n";
                        }

                        if (isset($debug['error'])) {
                            $report .= "🚨 Errore Globale: {$debug['error']['message']}\n";
                        }

                        // Salva il report
                        $logFile = storage_path('logs/wso2_debug_filament_' . now()->format('Y-m-d_H-i-s') . '.txt');
                        file_put_contents($logFile, $report);

                        Notification::make()
                            ->title('Debug Completato')
                            ->body('Report salvato in: wso2_debug_filament_*.txt')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Errore Debug')
                            ->body('Errore durante il debug: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->modalHeading('Debug WSO2')
                ->modalDescription('Esegue un test completo della connessione WSO2 e salva un report di debug.')
                ->modalIcon('fas-bug')
                ->modalSubmitActionLabel('Avvia Debug'),
        ];
    }
}
