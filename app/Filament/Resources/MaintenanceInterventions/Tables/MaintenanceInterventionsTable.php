<?php

namespace App\Filament\Resources\MaintenanceInterventions\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Filters\SelectFilter;

class MaintenanceInterventionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('application.name')
                    ->label('Applicazione')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('intervention_type')
                    ->label('Tipo Intervento')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'hardware' => 'gray',
                        'software' => 'info',
                        'facility' => 'success',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'hardware' => 'Hardware',
                        'software' => 'Software',
                        'facility' => 'Impianti',
                    }),
                TextColumn::make('company_name')
                    ->label('Ditta Esterna')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('operator_name')
                    ->label('Operatore')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'planned' => 'Pianificato',
                        'in_progress' => 'In corso',
                        'completed' => 'Completato',
                    }),
                TextColumn::make('started_at')
                    ->label('Inizio')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('ended_at')
                    ->label('Fine')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('intervention_type')
                    ->label('Tipo Intervento')
                    ->options([
                        'hardware' => 'Hardware',
                        'software' => 'Software',
                        'facility' => 'Impianti',
                    ]),
                SelectFilter::make('status')
                    ->label('Stato')
                    ->options([
                        'planned' => 'Pianificato',
                        'in_progress' => 'In corso',
                        'completed' => 'Completato',
                    ]),
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
