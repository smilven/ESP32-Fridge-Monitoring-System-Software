<?php

namespace App\Filament\Resources\Temperatures\Pages;

use App\Filament\Resources\Temperatures\TemperatureResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTemperature extends EditRecord
{
    protected static string $resource = TemperatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
