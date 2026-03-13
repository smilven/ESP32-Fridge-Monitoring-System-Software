<?php

namespace App\Filament\Resources\Fridges\Pages;

use App\Filament\Resources\Fridges\FridgeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFridge extends EditRecord
{
    protected static string $resource = FridgeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
