<?php

namespace App\Filament\Resources\Auditlogs;

use App\Filament\Resources\Auditlogs\Pages\CreateAuditLog;
use App\Filament\Resources\Auditlogs\Pages\EditAuditLog;
use App\Filament\Resources\Auditlogs\Pages\ListAuditLogs;
use App\Filament\Resources\Auditlogs\Schemas\AuditLogForm;
use App\Filament\Resources\Auditlogs\Tables\AuditLogsTable;
use App\Models\AuditLog;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use BackedEnum;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = 'fas-shield-alt';

    public static function form(Schema $schema): Schema
    {
        return AuditLogForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AuditLogsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
            'create' => CreateAuditLog::route('/create'),
            'edit' => EditAuditLog::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
