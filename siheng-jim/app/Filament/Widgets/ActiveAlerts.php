<?php

namespace App\Filament\Widgets;

use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Alert;

class ActiveAlerts extends TableWidget
{
    protected static ?string $heading = 'Active Alerts';

    protected int|string|array $columnSpan = 1;

    protected static ?int $sort = 2;

    protected static ?string $pollingInterval = '10s';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn (): Builder => Alert::query()
                    ->where('status', 'active')
                    ->latest()
            )
            ->columns([

                Tables\Columns\TextColumn::make('sensor.rom_address')
                    ->label('Sensor'),

                Tables\Columns\TextColumn::make('temperatureLog.temperature')
                    ->suffix(' °F')
                    ->color(fn ($state) => $state > 8 ? 'danger' : 'success'),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'danger' => 'Active',
                        'success' => 'Resolved',
                    ]),

                Tables\Columns\TextColumn::make('created_at')
                    ->since(),

            ])
            ->filters([
                //
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                //
            ]);
    }
}