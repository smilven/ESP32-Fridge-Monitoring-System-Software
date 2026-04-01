<?php

namespace App\Filament\Resources\TemperatureLogs\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Illuminate\Database\Eloquent\Builder;

class TemperatureLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
            'sensor.device.fridge.branch' // 一次性把整条链条查出来
        ]))
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