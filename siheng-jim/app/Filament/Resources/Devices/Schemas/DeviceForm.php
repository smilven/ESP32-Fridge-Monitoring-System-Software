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
                    ->readonly()
                    ->unique(ignoreRecord: true),

                    
                TextInput::make('device_token')
                    ->label('Device Token')
                    ->required()
                    ->readonly()
                    ->dehydrated(false),

                TextInput::make('serial_no')
                    ->label('Serial Number')
                    ->required()
                    ->readonly()
                    ->unique(ignoreRecord: true),

                Select::make('status')
                    ->hidden()
                    ->options([
                        'online' => 'Online',
                        'offline' => 'Offline',
                        'error' => 'error'
                    ]),

                TextInput::make('firmware_version')
                    ->label('Firmware Version'),

                TextInput::make('mqtt_broker')
                    ->required()
                    ->label('MQTT Broker'),

                TextInput::make('mqtt_port')
                    ->numeric()
                    ->required(),

                TextInput::make('mqtt_username')
                    ->label('MQTT Username'),

                TextInput::make('mqtt_password')
                    ->label('MQTT Password')
                    ->password(),

                TextInput::make('mqtt_topic')
                    ->required()
                    ->placeholder('device_uid/temp')
                    ->label('MQTT Topic'),


                DateTimePicker::make('last_seen')
                    ->hidden()
                    ->label('Last Seen'),
                    
            ]);
    }
}
