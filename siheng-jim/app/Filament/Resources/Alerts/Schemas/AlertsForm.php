<?php

namespace App\Filament\Resources\Alerts\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;

class AlertsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

                    Select::make('sensor_id')
                        ->relationship('sensor', 'sensor_name')
                        ->disabled(),

                    Select::make('temperature_log_id')
                        ->relationship('temperatureLog', 'id')
                        ->disabled(),

                    TextInput::make('alert_type')
                        ->disabled(),

                    Textarea::make('message')
                        ->disabled(),

                    Select::make('status')
                        ->options([
                            'active' => 'Active',
                            'resolved' => 'Resolved',
                        ]),

                    Select::make('resolved_by')
                        ->relationship('user', 'id'),

                    DateTimePicker::make('resolved_at'),
                ]);
    
    }
}