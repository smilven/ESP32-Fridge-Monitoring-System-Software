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
                        'maintenance' => 'Maintenance',
                    ])
                    ->required(),

                TextInput::make('firmware_version')
                    ->label('Firmware Version'),

                TextInput::make('wifi_ssid')
                    ->label('WiFi SSID'),

                TextInput::make('wifi_password')
                    ->label('WiFi Password')
                    ->password(),

                TextInput::make('mqtt_broker')
                    ->label('MQTT Broker'),

                TextInput::make('mqtt_port')
                    ->numeric()
                    ->default(1883),

                TextInput::make('mqtt_username')
                    ->label('MQTT Username'),

                       TextInput::make('mqtt_password')
                    ->label('MQTT Password')
                    ->password(),

                       TextInput::make('mqtt_topic')
                    ->label('MQTT Topic'),

                DateTimePicker::make('last_seen')
                    ->label('Last Seen'),
            ]);
    }
}
