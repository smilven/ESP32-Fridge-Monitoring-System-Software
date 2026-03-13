<?php

namespace App\Filament\Resources\Branches\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Schema;

class BranchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),

                TextInput::make('address'),

                TextInput::make('state'),

                TextInput::make('phone_number'),

                TextInput::make('type'),

                FileUpload::make('image_url')
                    ->directory('branches'),
            ]);
    }
}