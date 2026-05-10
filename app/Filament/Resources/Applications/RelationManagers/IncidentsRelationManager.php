<?php

namespace App\Filament\Resources\Applications\RelationManagers;

use App\Filament\Resources\Incidents\Schemas\IncidentForm;
use App\Filament\Resources\Incidents\Tables\IncidentsTable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class IncidentsRelationManager extends RelationManager
{
    protected static string $relationship = 'incidents';

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Schema $schema): Schema
    {
        return IncidentForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return IncidentsTable::configure($table)
            ->headerActions([
                \Filament\Actions\CreateAction::make(),
            ]);
    }
}
