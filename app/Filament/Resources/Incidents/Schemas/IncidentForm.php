<?php

namespace App\Filament\Resources\Incidents\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class IncidentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Dettagli Incidente')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Informazioni Generali')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Section::make()
                                    ->columns(2)
                                    ->schema([
                                        Select::make('application_id')
                                            ->label('Applicazione Coinvolta')
                                            ->relationship('application', 'name')
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, $set) {
                                                if ($state) {
                                                    $app = \App\Models\Application::find($state);
                                                    if ($app) {
                                                        $set('app_contacts', sprintf(
                                                            "Proprietario Scientifico: %s\nReferente Scientifico: %s\nTecnico Interno: %s\nTecnico Esterno: %s (%s)",
                                                            $app->scientific_owner,
                                                            $app->scientific_contact,
                                                            $app->internal_technical_contact,
                                                            $app->external_technical_contact,
                                                            $app->external_technical_email
                                                        ));
                                                    }
                                                }
                                            }),
                                        TextInput::make('title')
                                            ->label('Titolo Incidente')
                                            ->required()
                                            ->maxLength(255),
                                        Textarea::make('app_contacts')
                                            ->label('Referenti Applicazione')
                                            ->readOnly()
                                            ->rows(3)
                                            ->visible(fn ($get) => $get('application_id'))
                                            ->columnSpanFull(),
                                        Select::make('incident_type')
                                            ->label('Tipo di Incidente')
                                            ->options([
                                                'malware' => 'Malware',
                                                'phishing' => 'Phishing',
                                                'ddos' => 'DDoS',
                                                'unauthorized_access' => 'Accesso non autorizzato',
                                                'data_leak' => 'Fuga di dati',
                                                'hardware_failure' => 'Guasto Hardware',
                                                'other' => 'Altro',
                                            ])
                                            ->required(),
                                        Select::make('severity')
                                            ->label('Gravità')
                                            ->options([
                                                'low' => 'Bassa',
                                                'medium' => 'Media',
                                                'high' => 'Alta',
                                                'critical' => 'Critica',
                                            ])
                                            ->required(),
                                        Select::make('status')
                                            ->label('Stato')
                                            ->options([
                                                'open' => 'Aperto',
                                                'investigating' => 'In corso',
                                                'contained' => 'Contenuto',
                                                'resolved' => 'Risolto',
                                                'closed' => 'Chiuso',
                                            ])
                                            ->required(),
                                        DateTimePicker::make('detected_at')
                                            ->label('Rilevato il')
                                            ->required()
                                            ->default(now()),
                                        DateTimePicker::make('resolved_at')
                                            ->label('Risolto il'),
                                        Textarea::make('description')
                                            ->label('Descrizione')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tab::make('Notifica ACN')
                            ->icon('heroicon-o-megaphone')
                            ->schema([
                                Section::make()
                                    ->description('Dettagli per la notifica all\'Agenzia per la Cybersicurezza Nazionale')
                                    ->columns(2)
                                    ->schema([
                                        Toggle::make('acn_notified')
                                            ->label('Notificato ad ACN')
                                            ->reactive(),
                                        DateTimePicker::make('acn_notification_date')
                                            ->label('Data Notifica')
                                            ->visible(fn ($get) => $get('acn_notified')),
                                        TextInput::make('acn_protocol_number')
                                            ->label('Numero Protocollo')
                                            ->visible(fn ($get) => $get('acn_notified')),
                                    ]),
                            ]),
                        Tab::make('Analisi Impatto')
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                Section::make()
                                    ->schema([
                                        Textarea::make('impact_analysis')
                                            ->label('Analisi dell\'Impatto')
                                            ->placeholder('Descrivi l\'impatto tecnico e di business...'),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
