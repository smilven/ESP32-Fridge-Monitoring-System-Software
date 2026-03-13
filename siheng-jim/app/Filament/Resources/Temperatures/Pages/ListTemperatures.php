<?php

namespace App\Filament\Resources\Temperatures\Pages;

use App\Filament\Resources\Temperatures\TemperatureResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTemperatures extends ListRecords
{
    protected static string $resource = TemperatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
