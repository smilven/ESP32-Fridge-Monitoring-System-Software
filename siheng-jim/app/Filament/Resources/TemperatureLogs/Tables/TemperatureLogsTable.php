<?php

namespace App\Filament\Resources\TemperatureLogs\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;

class TemperatureLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('sensor.rom_address')
                    ->label('Sensor')
                    ->searchable(),

                TextColumn::make('sensor.device.serial_no')
                    ->label('Device'),

                TextColumn::make('sensor.device.fridge.model_number')
                    ->label('Fridge'),

                TextColumn::make('sensor.device.fridge.branch.name')
                    ->label('Branch'),

                TextColumn::make('temperature')
                    ->label('Temperature (°F)')
                    ->sortable(),

                IconColumn::make('alert_status')
                    ->boolean()
                    ->label('Alert'),

                TextColumn::make('recorded_at')
                    ->dateTime()
                    ->sortable(),
            ]);
    }
}