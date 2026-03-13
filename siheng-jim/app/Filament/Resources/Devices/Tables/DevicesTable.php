<?php

namespace App\Filament\Resources\Devices\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;

class DevicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),

                TextColumn::make('device_uid')
                    ->label('Device UID')
                    ->searchable(),

                TextColumn::make('device_token')
                    ->label('Device Token')
                    ->searchable(),

                TextColumn::make('fridge.model_number')
                    ->label('Fridge')
                    ->sortable(),

                BadgeColumn::make('status')
                    ->colors([
                        'success' => 'online',
                        'danger' => 'offline',
                        'warning' => 'maintenance',
                    ]),

                TextColumn::make('firmware_version')
                    ->label('Firmware'),

                TextColumn::make('mqtt_topic')
                    ->label('MQTT Topic')
                    ->limit(30),
                       
                    TextColumn::make('mqtt_username')
                    ->label('MQTT Username')
                    ->limit(30),   
                    
                    TextColumn::make('mqtt_password')
                    ->label('MQTT Password')
                    ->limit(30),

                TextColumn::make('last_seen')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->filters([
                //
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
