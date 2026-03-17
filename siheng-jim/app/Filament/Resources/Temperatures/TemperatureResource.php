<?php

namespace App\Filament\Resources\Temperatures;

use App\Filament\Resources\Temperatures\Pages\CreateTemperature;
use App\Filament\Resources\Temperatures\Pages\EditTemperature;
use App\Filament\Resources\Temperatures\Pages\ListTemperatures;
use App\Filament\Resources\Temperatures\Schemas\TemperatureForm;
use App\Filament\Resources\Temperatures\Tables\TemperaturesTable;
use App\Models\TemperatureLatest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TemperatureResource extends Resource
{
    protected static ?string $model = TemperatureLatest::class;
    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;
    protected static ?string $recordTitleAttribute = 'ID';

    public static function form(Schema $schema): Schema
    {
        return TemperatureForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TemperaturesTable::configure($table);
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
            'index' => ListTemperatures::route('/'),
        ];
    }
}
