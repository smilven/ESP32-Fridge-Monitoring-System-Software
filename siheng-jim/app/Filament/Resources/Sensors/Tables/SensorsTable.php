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
                    ->label('Device Serial Number')
                    ->searchable(),

                    TextColumn::make('rom_address')
                    ->label('ROM Address')
                    ->searchable(),

                TextColumn::make('position')
                ->sortable(),

                TextColumn::make('min_temp')
                    ->label('Min Temp')
                    ->sortable(),

                TextColumn::make('max_temp')
                    ->label('Max Temp')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
            ])

            ->recordActions([
                EditAction::make(),
            ])
            ->recordActionsColumnLabel('Actions')

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}