<?php

namespace App\Filament\Resources\Auditlogs\Schemas;

use Filament\Schemas\Schema;

class AuditLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }
}
