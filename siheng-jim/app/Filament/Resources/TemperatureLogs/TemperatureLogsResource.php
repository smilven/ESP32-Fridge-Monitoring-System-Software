<?php

namespace App\Filament\Resources\TemperatureLogs;


use App\Filament\Resources\TemperatureLogs\Pages\ListTemperatureLogs;
use App\Filament\Resources\TemperatureLogs\Tables\TemperatureLogsTable;
use App\Models\TemperatureLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TemperatureLogsResource extends Resource
{
    protected static ?int $navigationSort = 5;

    protected static ?string $model = TemperatureLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $recordTitleAttribute = 'id';

   public static function shouldRegisterNavigation():bool{
    return false;
   }

    public static function table(Table $table): Table
    {
        return TemperatureLogsTable::configure($table);
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
            'index' => ListTemperatureLogs::route('/'),
        ];
    }
}
