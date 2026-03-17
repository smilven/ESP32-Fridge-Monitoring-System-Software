<?php

namespace App\Filament\Resources\Temperatures\Tables;

use Dom\Text;
use Filament\Tables\Table;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;

use Filament\Actions\Action;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class TemperaturesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('sensor.rom_address')
                ->searchable()
                    ->label('ROM Address'),

                TextColumn::make('sensor.device.serial_no')
                    ->label('Device Serial Number')
                    ->searchable(),

                TextColumn::make('sensor.device.fridge.model_number')
                    ->label('Fridge'),

                TextColumn::make('sensor.device.fridge.branch.name')
                    ->searchable()
                    ->label('Branch')
                    ->searchable(),

                TextColumn::make('temperature')
                    ->label('Temperature (°F)')
                    ->sortable(),

                TextColumn::make('alert_status')
                ->label('Status')
                ->badge()
                ->color(fn ($state) => $state ? 'danger' : 'success')
                ->formatStateUsing(fn($state) => $state ? 'Danger' : 'Normal')
                ->sortable(),


                TextColumn::make('recorded_at')
                    ->dateTime(),

            ])

            ->filters([
                //
            ])

            ->recordActions([
                Action::make('download')
                    ->label('Download Logs')
                    ->url(fn($record) => url('/temperature/download/' . $record->sensor_id))
                    ->openUrlInNewTab()

            ])
            ->recordActionsColumnLabel('Download')

            ->toolbarActions([


            ]);
    }
}
