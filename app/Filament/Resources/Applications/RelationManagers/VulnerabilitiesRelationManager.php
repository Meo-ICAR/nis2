<?php

namespace App\Filament\Resources\Applications\RelationManagers;

use App\Filament\Resources\Vulnerabilities\Schemas\VulnerabilityForm;
use App\Filament\Resources\Vulnerabilities\Tables\VulnerabilitiesTable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class VulnerabilitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'vulnerabilities';

    protected static ?string $recordTitleAttribute = 'cve_id';

    public function form(Schema $schema): Schema
    {
        return VulnerabilityForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return VulnerabilitiesTable::configure($table)
            ->headerActions([
                \Filament\Actions\CreateAction::make(),
            ]);
    }
}
