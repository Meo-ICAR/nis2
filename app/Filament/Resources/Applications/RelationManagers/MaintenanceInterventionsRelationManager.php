<?php

namespace App\Filament\Resources\Applications\RelationManagers;

use App\Filament\Resources\MaintenanceInterventions\Schemas\MaintenanceInterventionForm;
use App\Filament\Resources\MaintenanceInterventions\Tables\MaintenanceInterventionsTable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class MaintenanceInterventionsRelationManager extends RelationManager
{
    protected static string $relationship = 'maintenanceInterventions';

    protected static ?string $recordTitleAttribute = 'company_name';

    public function form(Schema $schema): Schema
    {
        return MaintenanceInterventionForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return MaintenanceInterventionsTable::configure($table)
            ->headerActions([
                \Filament\Actions\CreateAction::make(),
            ]);
    }
}
