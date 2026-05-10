<?php

namespace App\Filament\Resources\Incidents\Tables;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;

class IncidentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('application.name')
                    ->label('Applicazione')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('title')
                    ->label('Titolo')
                    ->searchable()
                    ->limit(50),
                TextColumn::make('incident_type')
                    ->label('Tipo'),
                TextColumn::make('severity')
                    ->label('Gravità')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'low' => 'gray',
                        'medium' => 'warning',
                        'high' => 'orange',
                        'critical' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'low' => 'Bassa',
                        'medium' => 'Media',
                        'high' => 'Alta',
                        'critical' => 'Critica',
                    }),
                TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'open' => 'Aperto',
                        'investigating' => 'In corso',
                        'contained' => 'Contenuto',
                        'resolved' => 'Risolto',
                        'closed' => 'Chiuso',
                    }),
                IconColumn::make('acn_notified')
                    ->label('Notifica ACN')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('detected_at')
                    ->label('Rilevato il')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('severity')
                    ->label('Gravità')
                    ->options([
                        'low' => 'Bassa',
                        'medium' => 'Media',
                        'high' => 'Alta',
                        'critical' => 'Critica',
                    ]),
                SelectFilter::make('status')
                    ->label('Stato')
                    ->options([
                        'open' => 'Aperto',
                        'investigating' => 'In corso',
                        'contained' => 'Contenuto',
                        'resolved' => 'Risolto',
                        'closed' => 'Chiuso',
                    ]),
                TernaryFilter::make('acn_notified')
                    ->label('Notificato ad ACN'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
