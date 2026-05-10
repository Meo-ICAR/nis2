<?php

namespace App\Filament\Resources\Incidents;

use App\Filament\Resources\Incidents\Pages\CreateIncident;
use App\Filament\Resources\Incidents\Pages\EditIncident;
use App\Filament\Resources\Incidents\Pages\ListIncidents;
use App\Filament\Resources\Incidents\Schemas\IncidentForm;
use App\Filament\Resources\Incidents\Tables\IncidentsTable;
use App\Models\Incident;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class IncidentResource extends Resource
{
    protected static ?string $model = Incident::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static string|\UnitEnum|null $navigationGroup = 'NIS2 Compliance';

    public static function form(Schema $schema): Schema
    {
        return IncidentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IncidentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIncidents::route('/'),
            'create' => CreateIncident::route('/create'),
            'edit' => EditIncident::route('/{record}/edit'),
        ];
    }
}
