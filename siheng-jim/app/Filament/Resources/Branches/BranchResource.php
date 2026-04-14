<?php

namespace App\Filament\Resources\Branches;

use App\Filament\Resources\Branches\Pages\CreateBranch;
use App\Filament\Resources\Branches\Pages\EditBranch;
use App\Filament\Resources\Branches\Pages\ListBranches;
use App\Filament\Resources\Branches\Schemas\BranchForm;
use App\Filament\Resources\Branches\Tables\BranchesTable;
use App\Models\Branch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Filament\Resources\Branches\Pages\BranchTemperature;

class BranchResource extends Resource
{
    protected static ?string $slug = 'outlets';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Outlets';

    protected static ?string $navigationLabel ='Outlets';

    protected static ?string $breadcrumb ='Outlets';

    protected static ?string $heading = 'Outlets';
    
    

    protected static ?string $model = Branch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return BranchForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BranchesTable::configure($table);
    }

        public static function getModelLabel(): string
        {
            return 'outlet';
        }
    public static function getPluralLabel(): ?string
    {
        return 'Outlets';
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

        /*protected function getFooterWidgets(): array
        {
            return [
                BranchTemperature::class,
            ];
        }*/
       public static function getPages(): array
        {
            return [
                'index' => Pages\ListBranches::route('/'),
                'create' => Pages\CreateBranch::route('/create'),
                'edit' => Pages\EditBranch::route('/{record}/edit'),
                'temperature' => Pages\BranchTemperature::route('/{record}/temperature'), 
            ];
        }
}
