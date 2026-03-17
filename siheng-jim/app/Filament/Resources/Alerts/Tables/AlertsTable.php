<?php

namespace App\Filament\Resources\Alerts\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Actions\EditAction;

class AlertsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([

                TextColumn::make('sensor.rom_address')
                    ->label('Sensor')
                    ->searchable(),

                TextColumn::make('temperatureLog.temperature')
                    ->label('Temperature')
                    ->sortable()
                    ->suffix(' °F'),

                BadgeColumn::make('alert_type')
                    ->colors([
                        'danger' => 'HIGH_TEMP',
                        'warning' => 'LOW_TEMP',
                        'gray' => 'DEVICE_OFFLINE',
                    ]),

                TextColumn::make('message')
                    ->limit(50),

                BadgeColumn::make('status')
                    ->formatStateUsing(fn($state) => ucfirst($state))
                    ->colors([
                        'danger' => 'Active',
                        'success' => 'Resolved',
                    ]),

                BadgeColumn::make('escalated')
                    ->formatStateUsing(fn($state) => $state ? 'Yes' : 'No')
                    ->colors([
                        'danger' => fn($state) => $state,
                        'success' => fn($state) => !$state,
                    ]),

                BadgeColumn::make('reported')
                    ->formatStateUsing(fn($state) => $state ? 'Yes' : 'No')
                    ->colors([
                        'danger' => fn($state) => $state,
                        'success' => fn($state) => !$state,
                    ]),

                TextColumn::make('resolved_at')
                    ->sortable()
                    ->dateTime(),



                TextColumn::make('created_at')
                    ->sortable()
                    ->label('Occurred')
                    ->formatStateUsing(fn($state) => $state->diffForHumans()),
            ]);
    }
}
