<?php

namespace App\Filament\Resources\Applications\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ApplicationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('url')
                    ->url()
                    ->required(),
                TextInput::make('icon_url')
                    ->url(),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->required(),
                TextInput::make('scientific_owner'),
                TextInput::make('internal_technical_contact'),
                TextInput::make('external_technical_contact'),
                TextInput::make('external_technical_email')
                    ->email(),
                DatePicker::make('support_contract_expiry'),
                Textarea::make('contract_notes')
                    ->columnSpanFull(),
            ]);
    }
}
