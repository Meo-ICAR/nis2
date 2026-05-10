<?php

namespace App\Filament\Resources\Auditlogs\Pages;

use App\Filament\Resources\Auditlogs\AuditLogResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAuditLog extends CreateRecord
{
    protected static string $resource = AuditLogResource::class;
}
