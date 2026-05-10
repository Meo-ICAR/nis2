<?php

namespace App\Filament\Resources\MaintenanceInterventions\Pages;

use App\Filament\Resources\MaintenanceInterventions\MaintenanceInterventionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMaintenanceInterventions extends ListRecords
{
    protected static string $resource = MaintenanceInterventionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
