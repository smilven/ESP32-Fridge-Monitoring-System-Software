<?php

namespace App\Filament\Resources\Temperatures\Pages;

use App\Filament\Resources\Temperatures\TemperatureResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTemperature extends CreateRecord
{
    protected static string $resource = TemperatureResource::class;
}
