<?php

namespace App\Filament\Resources\MaintenanceInterventions;

use App\Filament\Resources\MaintenanceInterventions\Pages\CreateMaintenanceIntervention;
use App\Filament\Resources\MaintenanceInterventions\Pages\EditMaintenanceIntervention;
use App\Filament\Resources\MaintenanceInterventions\Pages\ListMaintenanceInterventions;
use App\Filament\Resources\MaintenanceInterventions\Schemas\MaintenanceInterventionForm;
use App\Filament\Resources\MaintenanceInterventions\Tables\MaintenanceInterventionsTable;
use App\Models\MaintenanceIntervention;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class MaintenanceInterventionResource extends Resource
{
    protected static ?string $model = MaintenanceIntervention::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static string|\UnitEnum|null $navigationGroup = 'NIS2 Compliance';

    protected static ?string $navigationLabel = 'External Maintenance';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return MaintenanceInterventionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MaintenanceInterventionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMaintenanceInterventions::route('/'),
            'create' => CreateMaintenanceIntervention::route('/create'),
            'edit' => EditMaintenanceIntervention::route('/{record}/edit'),
        ];
    }
}
