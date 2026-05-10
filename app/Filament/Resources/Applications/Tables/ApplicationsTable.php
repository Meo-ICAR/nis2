<?php

namespace App\Filament\Resources\Applications\Tables;

use App\Models\Application;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ApplicationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category')
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('internal_technical_contact')
                    ->label('Referente Tecnico')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('short_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('criticality_level')
                    ->label('Criticità NIS2')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'essential' => 'danger',
                        'important' => 'warning',
                        'standard' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'essential' => 'Essenziale',
                        'important' => 'Importante',
                        'standard' => 'Standard',
                        default => $state,
                    })
                    ->sortable(),
                IconColumn::make('is_strategic')
                    ->label('Strategica')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('')
                    ->trueColor('warning'),
                TextColumn::make('hosting_type')
                    ->label('Hosting')
                    ->toggleable(),
                TextColumn::make('support_contract_expiry')
                    ->label('Scadenza Contratto')
                    ->date()
                    ->sortable()
                    ->color(fn($record): string => $record->contractStatus() === 'expired' ? 'danger' : ($record->contractStatus() === 'expiring' ? 'warning' : 'success')),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('open_management')
                    ->label('Apri Cruscotto')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn(Application $record) => $record->management_url)
                    ->openUrlInNewTab()
                    ->visible(fn(Application $record) => !empty($record->management_url)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
