<?php

namespace App\Filament\Resources\Devices\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Builder;

class DevicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
        ->modifyQueryUsing(fn (Builder $query) => $query->with(['fridge']))
            ->columns([
                TextColumn::make('id'),
            
                TextColumn::make('device_uid')
                    ->label('Device UID')
                    ->searchable(),

                TextColumn::make('serial_no')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Device Serial Number'),

                TextColumn::make('device_token')
                    ->label('Device Token')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

                TextColumn::make('fridge.model_number')
                    ->label('Fridge')
                    ->searchable()
                    ->sortable(),
                    


                BadgeColumn::make('status')
                    ->colors([
                        'success' => 'online',
                        'danger' => 'offline',
                        'warning' => 'error',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'online' => 'Online',
                        'offline' => 'Offline',
                        'error' => 'Error',
                        default => 'Unknown',
                    }),
                    
                TextColumn::make('firmware_version')
                    ->label('Firmware'),

                TextColumn::make('mqtt_topic')
                    ->label('MQTT Topic')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(30),
                       
                    TextColumn::make('mqtt_username')
                    ->label('MQTT Username')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(30),   
                    
                    TextColumn::make('mqtt_password')
                    ->label('MQTT Password')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(30),

                TextColumn::make('last_seen')
                    ->label('Device Last Seen')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Device Created Date')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->dateTime() ])

            ->filters([
                //
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
