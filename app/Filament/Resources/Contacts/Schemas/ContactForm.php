<?php

namespace App\Filament\Resources\Contacts\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContactForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dettagli Contatto')
                    ->description('Informazioni anagrafiche e di contatto.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome Completo')
                            ->required()
                            ->prefixIcon('heroicon-o-user'),
                        TextInput::make('company')
                            ->label('Azienda / Ente')
                            ->prefixIcon('heroicon-o-building-office'),
                        TextInput::make('email')
                            ->label('Indirizzo Email')
                            ->email()
                            ->prefixIcon('heroicon-o-envelope'),
                        TextInput::make('phone')
                            ->label('Telefono / Mobile')
                            ->tel()
                            ->prefixIcon('heroicon-o-phone'),
                        Select::make('role')
                            ->label('Ruolo / Qualifica')
                            ->options([
                                'CISO' => 'CISO (Chief Information Security Officer)',
                                'DPO' => 'DPO (Data Protection Officer)',
                                'IT_MANAGER' => 'IT Manager',
                                'SYS_ADMIN' => 'Amministratore di Sistema',
                                'APP_OWNER' => 'Proprietario Applicativo',
                                'VENDOR_SUPPORT' => 'Supporto Tecnico Vendor',
                                'COMPLIANCE_OFFICER' => 'Compliance Officer',
                            ])
                            ->searchable()
                            ->prefixIcon('heroicon-o-briefcase'),
                        Textarea::make('notes')
                            ->label('Note Aggiuntive')
                            ->columnSpanFull()
                            ->rows(3),
                    ]),
            ]);
    }
}
