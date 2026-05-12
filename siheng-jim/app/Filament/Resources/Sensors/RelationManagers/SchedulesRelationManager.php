<?php

namespace App\Filament\Resources\Sensors\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;

class SchedulesRelationManager extends RelationManager
{
    protected static string $relationship = 'schedules';

    protected static ?string $title = 'Temperature Schedules';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('name')
                ->label('Schedule Name')
                ->placeholder('e.g. Morning, Peak Hours, Night')
                ->required()
                ->maxLength(100)
                ->columnSpanFull(),

            Forms\Components\TimePicker::make('start_time')
                ->label('Start Time')
                ->seconds(false)
                ->required(),

            Forms\Components\TimePicker::make('end_time')
                ->label('End Time')
                ->seconds(false)
                ->required()
                ->helperText('Overnight supported (e.g. 22:00 → 06:00): set end time before start time.'),

            Forms\Components\TextInput::make('min_temp')
                ->label('Min Temp (°F)')
                ->numeric()
                ->required(),

            Forms\Components\TextInput::make('max_temp')
                ->label('Max Temp (°F)')
                ->numeric()
                ->required(),

            Forms\Components\Toggle::make('is_active')
                ->label('Active')
                ->default(true)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Schedule Name')
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_time')
                    ->label('Start')
                    ->time('H:i'),

                Tables\Columns\TextColumn::make('end_time')
                    ->label('End')
                    ->time('H:i'),

                Tables\Columns\TextColumn::make('min_temp')
                    ->label('Min °F')
                    ->sortable(),

                Tables\Columns\TextColumn::make('max_temp')
                    ->label('Max °F')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}