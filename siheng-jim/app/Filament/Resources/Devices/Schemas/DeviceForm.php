<?php

namespace App\Filament\Resources\Devices\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;

class DeviceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('fridge_id')
                    ->relationship('fridge', 'model_number')
                    ->required(),
                TextInput::make('device_uid')
                    ->label('Device UID')
                    ->required()
                    ->unique(ignoreRecord: true),

                    
                TextInput::make('device_token')
                    ->label('Device Token')
                    ->required()
                    ->dehydrated(false),

                TextInput::make('serial_no')
                    ->label('Serial Number')
                    ->required()
                    ->unique(ignoreRecord: true),

                Select::make('status')
                    ->options([
                        'online' => 'Online',
                        'offline' => 'Offline',
                    ]),

                TextInput::make('firmware_version')
                    ->label('Firmware Version'),

                TextInput::make('mqtt_broker')
                    ->required()
                    ->label('MQTT Broker'),

                TextInput::make('mqtt_port')
                    ->numeric()
                    ->required()
                    ->default(1883),

                TextInput::make('mqtt_username')
                    ->required()
                    ->label('MQTT Username'),

                TextInput::make('mqtt_password')
                    ->label('MQTT Password')
                    ->password(),

                TextInput::make('mqtt_topic')
                    ->required()
                    ->label('MQTT Topic'),


                DateTimePicker::make('last_seen')
                    ->label('Last Seen'),
            ]);
    }
}
