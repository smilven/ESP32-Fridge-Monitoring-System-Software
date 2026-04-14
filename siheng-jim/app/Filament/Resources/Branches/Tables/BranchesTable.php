<?php

namespace App\Filament\Resources\Branches\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Actions\Action;
class BranchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_url')
                    ->label('Image')
                    ->square(),

                TextColumn::make('name')
                ->searchable(),

                TextColumn::make('state')
                ->sortable()
                 ->searchable(),

                TextColumn::make('phone_number'),



                TextColumn::make('type')
                ->sortable()
                ->searchable()
                ->label('Type')
                ->badge()
                ->color(fn ($state) => match ($state) {
                    'Corporate' => 'primary',
                    'Franchises' => 'info',
                    default => 'gray',
                }),



                TextColumn::make('created_at')
                ->sortable()
                ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('temperature')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => route(
                        'filament.admin.resources.outlets.temperature',
                        $record
                    )),

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
