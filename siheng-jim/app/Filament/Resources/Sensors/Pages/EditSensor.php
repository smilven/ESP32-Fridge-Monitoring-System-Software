<?php

namespace App\Filament\Resources\Sensors\Pages;

use App\Filament\Resources\Sensors\SensorResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSensor extends EditRecord
{
    protected static string $resource = SensorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
