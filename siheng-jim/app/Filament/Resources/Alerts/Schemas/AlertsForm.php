<?php

namespace App\Filament\Resources\Alerts\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;

class AlertsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
                ]);
    
    }
}