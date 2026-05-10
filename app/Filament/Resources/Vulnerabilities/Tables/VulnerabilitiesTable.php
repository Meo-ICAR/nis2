<?php

namespace App\Filament\Resources\Vulnerabilities\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Filters\SelectFilter;

class VulnerabilitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('application.name')
                    ->label('Applicazione')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('cve_id')
                    ->label('CVE ID')
                    ->searchable(),
                TextColumn::make('title')
                    ->label('Titolo')
                    ->searchable()
                    ->limit(50),
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
                        'identified' => 'Identificata',
                        'analyzing' => 'In analisi',
                        'fixing' => 'In risoluzione',
                        'resolved' => 'Risolta',
                        'wont_fix' => "Non verrà risolta",
                    }),
                TextColumn::make('discovery_date')
                    ->label('Data Scoperta')
                    ->date()
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
                        'identified' => 'Identificata',
                        'analyzing' => 'In analisi',
                        'fixing' => 'In risoluzione',
                        'resolved' => 'Risolta',
                        'wont_fix' => "Non verrà risolta",
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
