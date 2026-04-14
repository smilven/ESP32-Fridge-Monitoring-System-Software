<?php

namespace App\Filament\Resources\Temperatures\Tables;

use Dom\Text;
use Filament\Tables\Table;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;

use Filament\Actions\Action;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder;

class TemperaturesTable
{
    public static function configure(Table $table): Table
    {
        return $table
        ->modifyQueryUsing(fn (Builder $query) => $query->with([
            'sensor.device.fridge.branch' // 一次性把整条链条查出来
        ]))
            ->columns([
                TextColumn::make('sensor.device.fridge.branch.name')
                    ->searchable()
                    ->label('Outlet')
                    ->searchable(),
            
                TextColumn::make('sensor.device.fridge.model_number')
                    ->label('Fridge'),

                TextColumn::make('temperature')
                    ->label('Temperature (°F)')
                    ->sortable(),

                TextColumn::make('alert_status')
                ->label('Status')
                ->badge()
                ->color(fn ($state) => $state ? 'danger' : 'success')
                ->formatStateUsing(fn($state) => $state ? 'Danger' : 'Normal')
                ->sortable(),

                TextColumn::make('sensor.rom_address')
                ->searchable()
                    ->label('ROM Address'),

                TextColumn::make('sensor.device.serial_no')
                    ->label('Device Serial Number')
                    ->searchable(),

                TextColumn::make('sensor.device.status')
                    ->label('Device Status')
                   ->badge()
                    ->color(fn ($state) => match ($state) {
                        'online' => 'success',
                        'offline' => 'danger',
                        'error' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'online' => 'Online',
                        'offline' => 'Offline',
                        'error' => 'Error',
                        default => 'Unknown',
                    })
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
