<?php

namespace App\Filament\Resources\Fridges\Pages;

use App\Filament\Resources\Fridges\FridgeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFridges extends ListRecords
{
    protected static string $resource = FridgeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
