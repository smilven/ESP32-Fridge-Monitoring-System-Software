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

                TextInput::make('type')
                    ->required(),

                TextInput::make('model_number'),
                
                FileUpload::make('image_url')
                    ->directory('fridges'),
               


            ]);
            
            
    }
}
