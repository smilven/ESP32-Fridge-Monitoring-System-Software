<?php

namespace App\Filament\Resources\Alerts;

use App\Filament\Resources\Alerts\Pages\CreateAlerts;
use App\Filament\Resources\Alerts\Pages\EditAlerts;
use App\Filament\Resources\Alerts\Pages\ListAlerts;
use App\Filament\Resources\Alerts\Schemas\AlertsForm;
use App\Filament\Resources\Alerts\Tables\AlertsTable;
use App\Models\Alert;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AlertsResource extends Resource
{
    protected static ?int $navigationSort = 6;
    protected static ?string $model = Alert::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return AlertsForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AlertsTable::configure($table);
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
            'index' => ListAlerts::route('/'),
        ];
    }
}
