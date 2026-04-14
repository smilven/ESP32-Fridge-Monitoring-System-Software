<?php

namespace App\Filament\Resources\Fridges\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Database\Eloquent\Builder;

class FridgesTable
{
    public static function configure(Table $table): Table
    {
        return $table
        ->modifyQueryUsing(fn (Builder $query) => $query->with(['branch']))
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('branch.name')
                    ->label('Outlet')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Fridge Type')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('model_number')
                    ->label('Model Number')
                    ->searchable(),

                ImageColumn::make('image_url')
                    ->label('Image')
                    ->square(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])

            ->filters([
                //
            ])

            ->recordActions([
                EditAction::make(),
            ])
            ->recordActionsColumnLabel('Action')

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}