<?php

namespace App\Filament\Resources\Sensors\Pages;

use App\Filament\Resources\Sensors\SensorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSensors extends ListRecords
{
    protected static string $resource = SensorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
