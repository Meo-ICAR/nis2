<?php

namespace App\Filament\Resources\Incidents\Pages;

use App\Filament\Resources\Incidents\IncidentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListIncidents extends ListRecords
{
    protected static string $resource = IncidentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
