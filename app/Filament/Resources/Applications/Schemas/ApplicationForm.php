<?php

namespace App\Filament\Resources\Applications\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
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
                            ->icon('fas-info-circle')
                            ->schema([
                                Section::make('Anagrafica Base')
                                    ->description("Informazioni principali dell'applicazione o dell'asset.")
                                    ->icon('fas-id-badge')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('Nome Applicazione')
                                            ->required()
                                            ->maxLength(100)
                                            ->placeholder('es. Dell Enterprise Management')
                                            ->prefixIcon('fas-tag'),
                                        TextInput::make('short_name')
                                            ->label('Nome Breve')
                                            ->maxLength(20)
                                            ->placeholder('es. Dell EMC'),
                                        TextInput::make('url')
                                            ->label('URL Applicativo')
                                            ->url()
                                            ->required()
                                            ->columnSpanFull()
                                            ->prefixIcon('fas-link')
                                            ->suffixIcon('fas-external-link-alt'),
                                        TextInput::make('category')
                                            ->label('Categoria')
                                            ->placeholder('es. Infrastruttura, Sociale, HR')
                                            ->prefixIcon('fas-layer-group'),
                                        TextInput::make('project')
                                            ->label('Progetto / Ambito')
                                            ->placeholder('es. NIS2 Compliance')
                                            ->prefixIcon('fas-folder'),
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
                                            ->prefixIcon('fas-image'),
                                        TextInput::make('sort_order')
                                            ->label('Ordine Visualizzazione')
                                            ->numeric()
                                            ->default(0)
                                            ->prefixIcon('fas-sort-numeric-down'),
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
                                                    ->onIcon('fas-star')
                                                    ->offIcon('far-star'),
                                            ]),
                                    ]),
                            ]),
                        Tab::make('Infrastruttura')
                            ->icon('fas-microchip')
                            ->schema([
                                Section::make('Hosting & Management')
                                    ->description("Dettagli tecnici sul posizionamento e la gestione dell'asset.")
                                    ->icon('fas-server')
                                    ->columns(2)
                                    ->schema([
                                        Select::make('hosting_type')
                                            ->label('Tipo Hosting')
                                            ->options([
                                                'IASS' => 'IAAS VM',
                                                'CASS' => 'CAAS Container',
                                                'HPC' => 'High Performance Computing',
                                                'hybrid' => 'Ibrido',
                                            ])
                                            ->prefixIcon('fas-cloud'),
                                        TextInput::make('management_url')
                                            ->label('Console di Gestione (iDRAC/Cockpit/...)')
                                            ->url()
                                            ->prefixIcon('fas-wrench'),
                                        TextInput::make('service_tag')
                                            ->label('Service Tag / Serial Number')
                                            ->placeholder('es. ABC123D (Dell EMC)')
                                            ->prefixIcon('fas-key'),
                                        TextInput::make('external_id')
                                            ->label('ID Esterno (Console)')
                                            ->placeholder('Identificativo nel sistema sorgente')
                                            ->prefixIcon('fas-fingerprint'),
                                    ]),
                                Section::make('Ambienti & Sandbox')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('url_cockpit')
                                            ->label('URL Monitoraggio (Cockpit)')
                                            ->url()
                                            ->prefixIcon('fas-chart-bar'),
                                        TextInput::make('url_sandbox')
                                            ->label('URL Sandbox / Test')
                                            ->url()
                                            ->prefixIcon('fas-flask'),
                                        TextInput::make('url_documentation')
                                            ->label('URL Documentazione Tecnica')
                                            ->url()
                                            ->columnSpanFull()
                                            ->prefixIcon('fas-book'),
                                    ]),
                            ]),
                        Tab::make('Specifiche Tecniche')
                            ->icon('fas-cogs')
                            ->schema([
                                Section::make('Risorse Hardware')
                                    ->columns(3)
                                    ->schema([
                                        TextInput::make('cpu')
                                            ->label('CPU')
                                            ->placeholder('es. 4 Core')
                                            ->prefixIcon('fas-microchip'),
                                        TextInput::make('ram')
                                            ->label('RAM')
                                            ->placeholder('es. 16 GB')
                                            ->prefixIcon('fas-bolt'),
                                        TextInput::make('hd')
                                            ->label('Disco / Storage')
                                            ->placeholder('es. 100 GB SSD')
                                            ->prefixIcon('fas-hdd'),
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
                                            ->prefixIcon('fas-cube'),
                                        Textarea::make('ports')
                                            ->label('Porte da Aprire')
                                            ->placeholder('es. 80, 443, 3306')
                                            ->rows(2),
                                        //    ->prefixIcon('fas-exchange-alt')
                                    ]),
                            ]),
                        Tab::make('NIS2 & Sicurezza')
                            ->icon('fas-shield-alt')
                            ->schema([
                                Section::make('Classificazione NIS2')
                                    ->description('Parametri obbligatori per la direttiva Network and Information Security.')
                                    ->icon('fas-lock')
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
                                            ->prefixIcon('fas-exclamation-triangle'),
                                        Select::make('data_sensitivity')
                                            ->label('Sensibilità Dati (GDPR)')
                                            ->options([
                                                'common' => 'Dati Comuni',
                                                'sensitive' => 'Dati Particolari (Sociali/Sanitari)',
                                                'highly_sensitive' => 'Dati Giudiziari / Minori',
                                            ])
                                            ->prefixIcon('fas-eye'),
                                        Toggle::make('has_mfa')
                                            ->label('Autenticazione MFA')
                                            ->helperText("Indica se l'accesso richiede Multi-Factor Authentication.")
                                            ->onColor('success'),
                                        TextInput::make('backup_strategy')
                                            ->label('Strategia di Backup')
                                            ->placeholder('es. Giornaliero, Off-site, DR')
                                            ->prefixIcon('fas-archive'),
                                        TextInput::make('backup_replication')
                                            ->label('Replica Backup')
                                            ->placeholder('es. Sincrona, Asincrona, Nessuna')
                                            ->helperText('Configurazione replica per disaster recovery')
                                            ->prefixIcon('fas-sync'),
                                    ]),
                                Section::make('Credenziali SSO (WSO2)')
                                    ->description("Credenziali per l'integrazione con l'Identity Provider.")
                                    ->icon('fas-lock')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('client_id')
                                            ->label('Client ID')
                                            ->placeholder('Fornito da WSO2')
                                            ->prefixIcon('fas-id-badge'),
                                        TextInput::make('client_secret')
                                            ->label('Client Secret')
                                            ->password()
                                            ->revealable()
                                            ->placeholder('Fornito da WSO2')
                                            ->prefixIcon('fas-lock'),
                                        TextInput::make('url_job_anonimization_db')
                                            ->label('Database Anonimizzazione Job')
                                            ->url()
                                            ->placeholder('URL database per anonimizzazione job')
                                            ->helperText('Database dedicato per processi di anonimizzazione')
                                            ->prefixIcon('fas-database')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tab::make('Contatti & Supporto')
                            ->icon('fas-users')
                            ->schema([
                                Section::make('Referenti Interni')
                                    ->columns(2)
                                    ->schema([
                                        Select::make('suggest_internal')
                                            ->label('💡 Suggerisci da Rubrica')
                                            ->options(\App\Models\Contact::all()->pluck('name', 'id'))
                                            ->searchable()
                                            ->dehydrated(false)
                                            ->columnSpanFull()
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, $set) {
                                                if ($state) {
                                                    $contact = \App\Models\Contact::find($state);
                                                    if ($contact) {
                                                        // This is a generic suggestion, we fill the focused field or all?
                                                        // Let's fill the nearest logic.
                                                        $set('scientific_owner', $contact->name);
                                                    }
                                                }
                                            })
                                            ->hint('Seleziona un contatto per precaricare il nome'),
                                        TextInput::make('scientific_owner')
                                            ->label('Proprietario Business (Owner)')
                                            ->prefixIcon('fas-user'),
                                        TextInput::make('scientific_contact')
                                            ->label('Referente Scientifico')
                                            ->prefixIcon('fas-user-circle'),
                                        TextInput::make('internal_technical_contact')
                                            ->label('Referente Tecnico Interno')
                                            ->columnSpanFull()
                                            ->prefixIcon('fas-terminal'),
                                    ]),
                                Section::make('Supporto Esterno & Contratti')
                                    ->columns(2)
                                    ->schema([
                                        Select::make('suggest_external')
                                            ->label('💡 Suggerisci da Rubrica')
                                            ->options(\App\Models\Contact::all()->pluck('name', 'id'))
                                            ->searchable()
                                            ->dehydrated(false)
                                            ->columnSpanFull()
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, $set) {
                                                if ($state) {
                                                    $contact = \App\Models\Contact::find($state);
                                                    if ($contact) {
                                                        $set('external_technical_contact', $contact->name);
                                                        $set('external_technical_email', $contact->email);
                                                    }
                                                }
                                            }),
                                        TextInput::make('external_technical_contact')
                                            ->label('Supporto Vendor')
                                            ->prefixIcon('fas-file-contract'),
                                        TextInput::make('external_technical_email')
                                            ->label('Email Supporto')
                                            ->email()
                                            ->prefixIcon('fas-envelope'),
                                        DatePicker::make('support_contract_expiry')
                                            ->label('Scadenza Contratto Supporto')
                                            ->prefixIcon('fas-calendar-alt'),
                                        Textarea::make('contract_notes')
                                            ->label('Note Contrattuali')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tab::make('Integrazione & API')
                            ->icon('fas-network-wired')
                            ->schema([
                                Section::make('Configurazione HUB')
                                    ->description("Configura come questo HUB si collega o riceve dati dall'applicazione.")
                                    ->columns(2)
                                    ->schema([
                                        Select::make('connector_type')
                                            ->label('Tipo Connettore (Strategy)')
                                            ->options([
                                                'generic_rest' => 'Generic REST API',
                                                'hpc' => 'HPC Cluster Connector',
                                                'wso2' => 'WSO2 Identity Manager',
                                            ])
                                            ->prefixIcon('fas-plug')
                                            ->reactive(),
                                        TextInput::make('webhook_token')
                                            ->label('Webhook Token')
                                            ->helperText('Usa questo token per inviare allarmi a: /api/v1/webhooks/{token}')
                                            ->readOnly()
                                            ->extraInputAttributes(['onclick' => 'this.select()'])
                                            ->hintAction(
                                                \Filament\Actions\Action::make('generateToken')
                                                    ->icon('fas-sync')
                                                    ->action(fn($set) => $set('webhook_token', (string) \Illuminate\Support\Str::uuid()))
                                            )
                                            ->prefixIcon('fas-key'),
                                        Section::make('WSO2 Configuration')
                                            ->visible(fn($get) => $get('connector_type') === 'wso2')
                                            ->columns(2)
                                            ->schema([
                                                TextInput::make('wso2_base_url')
                                                    ->label('WSO2 Base URL')
                                                    ->placeholder('https://is.company.com:9443')
                                                    ->prefixIcon('fas-link'),
                                                TextInput::make('wso2_tenant_domain')
                                                    ->label('WSO2 Tenant Domain')
                                                    ->placeholder('carbon.super')
                                                    ->prefixIcon('fas-building'),
                                            ]),
                                        Textarea::make('integration_config')
                                            ->label('Configurazione JSON')
                                            ->helperText('Parametri extra in formato JSON per il connettore.')
                                            ->columnSpanFull()
                                            ->rows(4),
                                    ]),
                            ]),
                        Tab::make('Documenti')
                            ->icon('fas-file-alt')
                            ->schema([
                                Section::make('Allegati')
                                    ->description("Carica documenti, immagini e altri file relativi all'applicazione.")
                                    ->icon('fas-paperclip')
                                    ->schema([
                                        SpatieMediaLibraryFileUpload::make('documents')
                                            ->label('Documenti e Allegati')
                                            ->collection('documents')
                                            ->multiple()
                                            ->directory('applications/documents')
                                            ->visibility('private')
                                            ->maxSize(10240)  // 10MB
                                            ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.*', 'text/plain'])
                                            ->helperText('Carica documenti PDF, immagini, Word o file di testo. Max 10MB per file.')
                                            ->columnSpanFull(),
                                        SpatieMediaLibraryFileUpload::make('screenshots')
                                            ->label('Screenshot e Immagini')
                                            ->collection('screenshots')
                                            ->multiple()
                                            ->directory('applications/screenshots')
                                            ->image()
                                            ->imageEditor()
                                            ->maxSize(5120)  // 5MB
                                            ->acceptedFileTypes(['image/*'])
                                            ->helperText("Carica screenshot o immagini dell'interfaccia. Max 5MB per immagine.")
                                            ->columnSpanFull(),
                                        SpatieMediaLibraryFileUpload::make('contracts')
                                            ->label('Contratti e Documenti Legali')
                                            ->collection('contracts')
                                            ->multiple()
                                            ->directory('applications/contracts')
                                            ->visibility('private')
                                            ->maxSize(15360)  // 15MB
                                            ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.*'])
                                            ->helperText('Carica contratti, SLA, documenti legali. Max 15MB per file.')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
