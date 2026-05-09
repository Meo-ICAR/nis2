<?php

namespace App\Filament\Resources\Applications\Pages;

use App\Filament\Resources\Applications\ApplicationResource;
use App\Jobs\SyncWSO2ApplicationsJob;
use App\Services\WSO2ApplicationService;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListApplications extends ListRecords
{
    protected static string $resource = ApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Actions\Action::make('sync_wso2_applications')
                ->label('Sync Applicazioni WSO2')
                ->icon('fas-cloud-download-alt')
                ->color('info')
                ->form([
                    Checkbox::make('async')
                        ->label('Esegui in background (consigliato)')
                        ->default(true)
                        ->helperText("Se spuntato, il sync verrà eseguito in background senza bloccare l'interfaccia."),
                    TextInput::make('application_id')
                        ->label('ID Applicazione Specifica (opzionale)')
                        ->placeholder('Lascia vuoto per sincronizzare tutte')
                        ->helperText("Inserisci l'ID WSO2 di un'applicazione specifica da sincronizzare."),
                ])
                ->action(function (array $data, WSO2ApplicationService $wso2AppService) {
                    try {
                        if ($data['async']) {
                            // Esegui in background
                            SyncWSO2ApplicationsJob::dispatch();

                            Notification::make()
                                ->title('Sync Avviato')
                                ->body('La sincronizzazione delle applicazioni WSO2 è stata avviata in background.')
                                ->success()
                                ->send();
                        } else {
                            // Esegui immediatamente
                            if (!empty($data['application_id'])) {
                                // Sync singola applicazione
                                $application = $wso2AppService->syncApplicationById($data['application_id']);

                                Notification::make()
                                    ->title('Sync Completato')
                                    ->body("Applicazione sincronizzata: {$application->name}")
                                    ->success()
                                    ->send();
                            } else {
                                // Sync tutte le applicazioni
                                $result = $wso2AppService->syncApplications();

                                Notification::make()
                                    ->title('Sync Completato')
                                    ->body("Sincronizzazione completata: {$result['created']} nuove, {$result['updated']} aggiornate, {$result['skipped']} saltate.")
                                    ->success()
                                    ->send();
                            }
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
                ->modalHeading('Sincronizza Applicazioni da WSO2')
                ->modalDescription('Questo processo importerà le applicazioni OAuth2 da WSO2 e le sincronizzerà con il database locale.')
                ->modalIcon('fas-cloud-download-alt')
                ->modalSubmitActionLabel('Avvia Sync'),
        ];
    }
}
