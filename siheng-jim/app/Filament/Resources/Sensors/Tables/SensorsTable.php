<?php

namespace App\Filament\Resources\Sensors\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;

class SensorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),

                TextColumn::make('device.serial_no')
                    ->label('Device')
                    ->searchable(),

                    TextColumn::make('rom_address')
                    ->label('ROM Address')
                    ->searchable(),

                TextColumn::make('name')
                    ->searchable(),

                TextColumn::make('position'),

                TextColumn::make('min_temp')
                    ->label('Min Temp'),

                TextColumn::make('max_temp')
                    ->label('Max Temp'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])

            ->recordActions([
                EditAction::make(),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}