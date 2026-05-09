<?php

namespace App\Filament\Resources\Applications\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;

class ApplicationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Dettagli Applicazione')
                    ->tabs([
                        Tab::make('Generale')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Section::make('Anagrafica Base')
                                    ->description("Informazioni principali dell'applicazione o dell'asset.")
                                    ->icon('heroicon-o-identification')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('Nome Applicazione')
                                            ->required()
                                            ->maxLength(100)
                                            ->placeholder('es. Dell Enterprise Management')
                                            ->prefixIcon('heroicon-o-tag'),
                                        TextInput::make('short_name')
                                            ->label('Nome Breve')
                                            ->maxLength(20)
                                            ->placeholder('es. Dell EMC'),
                                        TextInput::make('url')
                                            ->label('URL Applicativo')
                                            ->url()
                                            ->required()
                                            ->columnSpanFull()
                                            ->prefixIcon('heroicon-o-link')
                                            ->suffixIcon('heroicon-o-arrow-top-right-on-square'),
                                        TextInput::make('category')
                                            ->label('Categoria')
                                            ->placeholder('es. Infrastruttura, Sociale, HR')
                                            ->prefixIcon('heroicon-o-rectangle-stack'),
                                        TextInput::make('project')
                                            ->label('Progetto / Ambito')
                                            ->placeholder('es. NIS2 Compliance')
                                            ->prefixIcon('heroicon-o-folder'),
                                        Textarea::make('description')
                                            ->label('Descrizione Funzionale')
                                            ->columnSpanFull()
                                            ->rows(3),
                                    ]),
                                Section::make('Aspetto e Ordinamento')
                                    ->columns(3)
                                    ->schema([
                                        TextInput::make('icon_url')
                                            ->label('URL Icona')
                                            ->url()
                                            ->prefixIcon('heroicon-o-photo'),
                                        TextInput::make('sort_order')
                                            ->label('Ordine Visualizzazione')
                                            ->numeric()
                                            ->default(0)
                                            ->prefixIcon('heroicon-o-bars-3-bottom-left'),
                                        Grid::make(1)
                                            ->columnSpan(1)
                                            ->schema([
                                                Toggle::make('is_active')
                                                    ->label('Stato Attivo')
                                                    ->default(true)
                                                    ->onColor('success')
                                                    ->offColor('danger'),
                                                Toggle::make('is_strategic')
                                                    ->label('Asset Strategico')
                                                    ->onIcon('heroicon-m-star')
                                                    ->offIcon('heroicon-o-star'),
                                            ]),
                                    ]),
                            ]),
                        Tab::make('Infrastruttura')
                            ->icon('heroicon-o-cpu-chip')
                            ->schema([
                                Section::make('Hosting & Management')
                                    ->description("Dettagli tecnici sul posizionamento e la gestione dell'asset.")
                                    ->icon('heroicon-o-server-stack')
                                    ->columns(2)
                                    ->schema([
                                        Select::make('hosting_type')
                                            ->label('Tipo Hosting')
                                            ->options([
                                                'on-premise' => 'On-Premise (Datacenter)',
                                                'cloud' => 'Cloud (SaaS/PaaS)',
                                                'hybrid' => 'Ibrido',
                                            ])
                                            ->prefixIcon('heroicon-o-cloud'),
                                        TextInput::make('management_url')
                                            ->label('Console di Gestione (iDRAC/Admin)')
                                            ->url()
                                            ->prefixIcon('heroicon-o-wrench-screwdriver'),
                                        TextInput::make('service_tag')
                                            ->label('Service Tag / Serial Number')
                                            ->placeholder('es. ABC123D (Dell EMC)')
                                            ->prefixIcon('heroicon-o-key'),
                                        TextInput::make('external_id')
                                            ->label('ID Esterno (Console)')
                                            ->placeholder('Identificativo nel sistema sorgente')
                                            ->prefixIcon('heroicon-o-finger-print'),
                                    ]),
                                Section::make('Ambienti & Sandbox')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('url_cockpit')
                                            ->label('URL Monitoraggio (Cockpit)')
                                            ->url()
                                            ->prefixIcon('heroicon-o-chart-bar'),
                                        TextInput::make('url_sandbox')
                                            ->label('URL Sandbox / Test')
                                            ->url()
                                            ->prefixIcon('heroicon-o-beaker'),
                                        TextInput::make('url_documentation')
                                            ->label('URL Documentazione Tecnica')
                                            ->url()
                                            ->columnSpanFull()
                                            ->prefixIcon('heroicon-o-book-open'),
                                    ]),
                            ]),
                        Tab::make('Specifiche Tecniche')
                            ->icon('heroicon-o-adjustments-horizontal')
                            ->schema([
                                Section::make('Risorse Hardware')
                                    ->columns(3)
                                    ->schema([
                                        TextInput::make('cpu')
                                            ->label('CPU')
                                            ->placeholder('es. 4 Core')
                                            ->prefixIcon('heroicon-o-cpu-chip'),
                                        TextInput::make('ram')
                                            ->label('RAM')
                                            ->placeholder('es. 16 GB')
                                            ->prefixIcon('heroicon-o-bolt'),
                                        TextInput::make('hd')
                                            ->label('Disco / Storage')
                                            ->placeholder('es. 100 GB SSD')
                                            ->prefixIcon('heroicon-o-hard-drive'),
                                    ]),
                                Section::make('Ambiente di Esecuzione')
                                    ->columns(2)
                                    ->schema([
                                        Select::make('runtime_type')
                                            ->label('Tipo Ambiente')
                                            ->options([
                                                'vm' => 'Virtual Machine (VM)',
                                                'container' => 'Container (Docker/LXC)',
                                            ])
                                            ->prefixIcon('heroicon-o-cube'),
                                        Textarea::make('ports')
                                            ->label('Porte da Aprire')
                                            ->placeholder('es. 80, 443, 3306')
                                            ->rows(2)
                                            ->prefixIcon('heroicon-o-arrows-right-left'),
                                    ]),
                            ]),
                        Tab::make('NIS2 & Sicurezza')
                            ->icon('heroicon-o-shield-check')
                            ->schema([
                                Section::make('Classificazione NIS2')
                                    ->description('Parametri obbligatori per la direttiva Network and Information Security.')
                                    ->icon('heroicon-o-lock-closed')
                                    ->columns(2)
                                    ->schema([
                                        Select::make('criticality_level')
                                            ->label('Criticità NIS2')
                                            ->required()
                                            ->options([
                                                'essential' => '🔴 Essenziale',
                                                'important' => '🟡 Importante',
                                                'standard' => '🟢 Standard / Altro',
                                            ])
                                            ->prefixIcon('heroicon-o-exclamation-triangle'),
                                        Select::make('data_sensitivity')
                                            ->label('Sensibilità Dati (GDPR)')
                                            ->options([
                                                'common' => 'Dati Comuni',
                                                'sensitive' => 'Dati Particolari (Sociali/Sanitari)',
                                                'highly_sensitive' => 'Dati Giudiziari / Minori',
                                            ])
                                            ->prefixIcon('heroicon-o-eye'),
                                        Toggle::make('has_mfa')
                                            ->label('Autenticazione MFA')
                                            ->helperText("Indica se l'accesso richiede Multi-Factor Authentication.")
                                            ->onColor('success'),
                                        TextInput::make('backup_strategy')
                                            ->label('Strategia di Backup')
                                            ->placeholder('es. Giornaliero, Off-site, DR')
                                            ->prefixIcon('heroicon-o-archive-box-arrow-down'),
                                    ]),
                                Section::make('Credenziali SSO (WSO2)')
                                    ->description('Credenziali per l\'integrazione con l\'Identity Provider.')
                                    ->icon('heroicon-o-key')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('client_id')
                                            ->label('Client ID')
                                            ->placeholder('Fornito da WSO2')
                                            ->prefixIcon('heroicon-o-identification'),
                                        TextInput::make('client_secret')
                                            ->label('Client Secret')
                                            ->password()
                                            ->revealable()
                                            ->placeholder('Fornito da WSO2')
                                            ->prefixIcon('heroicon-o-lock-closed'),
                                    ]),
                            ]),
                        Tab::make('Contatti & Supporto')
                            ->icon('heroicon-o-user-group')
                            ->schema([
                                Section::make('Referenti Interni')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('scientific_owner')
                                            ->label('Proprietario Business (Owner)')
                                            ->prefixIcon('heroicon-o-user'),
                                        TextInput::make('scientific_contact')
                                            ->label('Referente Scientifico')
                                            ->prefixIcon('heroicon-o-user-circle'),
                                        TextInput::make('internal_technical_contact')
                                            ->label('Referente Tecnico Interno')
                                            ->columnSpanFull()
                                            ->prefixIcon('heroicon-o-command-line'),
                                    ]),
                                Section::make('Supporto Esterno & Contratti')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('external_technical_contact')
                                            ->label('Supporto Vendor')
                                            ->prefixIcon('heroicon-o-building-office'),
                                        TextInput::make('external_technical_email')
                                            ->label('Email Supporto')
                                            ->email()
                                            ->prefixIcon('heroicon-o-envelope'),
                                        DatePicker::make('support_contract_expiry')
                                            ->label('Scadenza Contratto Supporto')
                                            ->prefixIcon('heroicon-o-calendar-days'),
                                        Textarea::make('contract_notes')
                                            ->label('Note Contrattuali')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
