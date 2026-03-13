<?php

namespace App\Filament\Resources\Fridges;

use App\Filament\Resources\Fridges\Pages\CreateFridge;
use App\Filament\Resources\Fridges\Pages\EditFridge;
use App\Filament\Resources\Fridges\Pages\ListFridges;
use App\Filament\Resources\Fridges\Schemas\FridgeForm;
use App\Filament\Resources\Fridges\Tables\FridgesTable;
use App\Models\Fridge;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FridgeResource extends Resource
{
    protected static ?int $navigationSort = 2;

    protected static ?string $model = Fridge::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static ?string $recordTitleAttribute = 'model_number';

    public static function form(Schema $schema): Schema
    {
        return FridgeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FridgesTable::configure($table);
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
            'index' => ListFridges::route('/'),
            'create' => CreateFridge::route('/create'),
            'edit' => EditFridge::route('/{record}/edit'),
        ];
    }
}
