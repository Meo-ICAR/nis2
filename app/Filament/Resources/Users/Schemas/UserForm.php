<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->schema([
                        Section::make('Informazioni Profilo')
                            ->description('Dati anagrafici e identificativi univoci.')
                            ->icon('heroicon-o-user-circle')
                            ->columnSpan(2)
                            ->columns(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nome Completo')
                                    ->required()
                                    ->prefixIcon('heroicon-o-user'),
                                TextInput::make('email')
                                    ->label('Indirizzo Email')
                                    ->email()
                                    ->required()
                                    ->prefixIcon('heroicon-o-envelope'),
                                TextInput::make('sub')
                                    ->label('Identificativo OIDC (SUB)')
                                    ->helperText('Identificativo univoco fornito dal provider di identità.')
                                    ->prefixIcon('heroicon-o-finger-print')
                                    ->disabled(),
                                DateTimePicker::make('email_verified_at')
                                    ->label('Email Verificata il')
                                    ->prefixIcon('heroicon-o-check-badge')
                                    ->disabled(),
                            ]),
                        Section::make('Stato & Ruoli')
                            ->description('Configurazione accesso e permessi.')
                            ->icon('heroicon-o-shield-check')
                            ->columnSpan(1)
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('Utente Attivo')
                                    ->helperText("Disabilita per bloccare l'accesso al portale.")
                                    ->default(true)
                                    ->onColor('success')
                                    ->offColor('danger'),
                                Toggle::make('is_admin')
                                    ->label('Amministratore')
                                    ->helperText("Concede l'accesso al pannello di configurazione.")
                                    ->onIcon('heroicon-m-shield-check')
                                    ->offIcon('heroicon-o-shield-exclamation'),
                                DateTimePicker::make('last_login_at')
                                    ->label('Ultimo Accesso')
                                    ->prefixIcon('heroicon-o-clock')
                                    ->disabled(),
                            ]),
                    ]),
                Section::make('Sicurezza')
                    ->collapsed()
                    ->icon('heroicon-o-key')
                    ->schema([
                        TextInput::make('password')
                            ->label('Nuova Password')
                            ->password()
                            ->required(fn(string $operation): bool => $operation === 'create')
                            ->dehydrated(fn(?string $state) => filled($state))
                            ->helperText("Lascia vuoto se l'autenticazione è gestita via OIDC.")
                            ->prefixIcon('heroicon-o-lock-closed'),
                    ]),
            ]);
    }
}
