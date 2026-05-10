<?php

namespace App\Filament\Resources\MaintenanceInterventions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class MaintenanceInterventionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Dettagli Intervento')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Descrizione')
                            ->icon('heroicon-o-document-text')
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
                                        Select::make('intervention_type')
                                            ->label('Tipo di Intervento')
                                            ->options([
                                                'hardware' => 'Hardware',
                                                'software' => 'Software',
                                                'facility' => 'Facility (Impianti)',
                                            ])
                                            ->required(),
                                        Textarea::make('app_contacts')
                                            ->label('Referenti Applicazione')
                                            ->readOnly()
                                            ->rows(3)
                                            ->visible(fn ($get) => $get('application_id'))
                                            ->columnSpanFull(),
                                        Select::make('suggest_contact')
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
                                                        $set('company_name', $contact->company);
                                                        $set('operator_name', $contact->name);
                                                    }
                                                }
                                            }),
                                        TextInput::make('company_name')
                                            ->label('Ditta Esterna')
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('operator_name')
                                            ->label('Nome Operatore')
                                            ->maxLength(255),
                                        Textarea::make('description')
                                            ->label('Descrizione Intervento')
                                            ->required()
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tab::make('Tempistiche e Stato')
                            ->icon('heroicon-o-clock')
                            ->schema([
                                Section::make()
                                    ->columns(2)
                                    ->schema([
                                        DateTimePicker::make('started_at')
                                            ->label('Inizio Intervento')
                                            ->required()
                                            ->default(now()),
                                        DateTimePicker::make('ended_at')
                                            ->label('Fine Intervento'),
                                        Select::make('status')
                                            ->label('Stato')
                                            ->options([
                                                'planned' => 'Pianificato',
                                                'in_progress' => 'In corso',
                                                'completed' => 'Completato',
                                            ])
                                            ->required(),
                                        Textarea::make('notes')
                                            ->label('Note')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
