<?php

namespace App\Filament\Resources\Branches\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Schema;
USE Filament\Forms\Components\Select;
use Filament\Tables\Columns\SelectColumn;
class BranchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                ->required(),

                TextInput::make('address')
                ->required(),

                Select::make('state')
                    ->label('State')
                    ->required()
                    ->options([
                        'Johor' => 'Johor',
                        'Kedah' => 'Kedah',
                        'Kelantan' => 'Kelantan',
                        'Melaka' => 'Melaka',
                        'Negeri Sembilan' => 'Negeri Sembilan',
                        'Pahang' => 'Pahang',
                        'Perak' => 'Perak',
                        'Perlis' => 'Perlis',
                        'Pulau Pinang' => 'Pulau Pinang',
                        'Sabah' => 'Sabah',
                        'Sarawak' => 'Sarawak',
                        'Selangor' => 'Selangor',
                        'Terengganu' => 'Terengganu',
                        'Kuala Lumpur' => 'Kuala Lumpur',
                        'Labuan' => 'Labuan',
                        'Putrajaya' => 'Putrajaya',
                    ])
                    ->placeholder('Select a state'),

                TextInput::make('phone_number'),

                Select::make('type')
                ->label('Branch Type')
                ->options([
                        'Corporate MB' => 'Corporate MB',
                        'Franchises MB' => 'Franchises MB',
                    ])
                ->placeholder('Select MB type')
                ->required(),

                FileUpload::make('image_url')
                    ->directory('branches'),
            ]);
    }
}