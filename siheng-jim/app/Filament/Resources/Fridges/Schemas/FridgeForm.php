<?php

namespace App\Filament\Resources\Fridges\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;

class FridgeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->required(),

                Select::make('type')
                ->label('Fridge Type')
                ->options([
                    'Upright Chiller ' => 'Upright Chiller',
                    'Upright Freezer' => 'Upright Freezer',
                    'Upright Chiller & Freezer' => 'Upright Chiller & Freezer',
                    'Cool Room' => 'Cool Room'
                ])
                ->placeholder('Select Fridge Type')
                ->required(),

                TextInput::make('model_number'),
                
                FileUpload::make('image_url')
                    ->directory('fridges'),
               


            ]);
            
            
    }
}
