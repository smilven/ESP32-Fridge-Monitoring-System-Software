<?php

namespace App\Filament\Resources\Sensors\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;

class SensorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('device_id')
                    ->label('Device')
                    ->relationship('device', 'serial_no')
                    ->required(),

                Forms\Components\TextInput::make('name')
                    ->required(),

                 Forms\Components\TextInput::make('rom_address')
                    ->required(),   

                Forms\Components\TextInput::make('position')
                    ->placeholder('Top / Bottom / Door'),

                Forms\Components\TextInput::make('max_temp')
                    ->numeric()
                    ->required(),

                Forms\Components\TextInput::make('min_temp')
                    ->numeric()
                    ->required(),
            ]);
    }
}