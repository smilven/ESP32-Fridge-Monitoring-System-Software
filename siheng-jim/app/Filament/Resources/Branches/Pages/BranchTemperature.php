<?php

namespace App\Filament\Resources\Branches\Pages;

use Filament\Resources\Pages\Page;
use App\Filament\Resources\Branches\BranchResource;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Sensor;
use App\Models\Branch;

class BranchTemperature extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = BranchResource::class;

    protected static ?string $title = 'Branch Temperature'; // breadcrumb 用

    protected string $view = 'filament.resources.branches.pages.branch-temperature';

    public $record;

    public function mount($record)
    {
        $this->record = Branch::findOrFail($record);
    }

   public function getHeading(): string
        {
            return $this->record->name;
        }

        public function getSubheading(): ?string
        {
            return 'Temperature Monitoring';
        }

    // ✅ 自动刷新
    protected function getTablePollingInterval(): ?string
    {
        return '5s';
    }

    protected function getTableQuery(): Builder
    {
        return Sensor::query()
            ->whereHas('device.fridge', function ($q) {
                $q->where('branch_id', $this->record->id);
            })
            ->with(['temperatureLatest', 'device.fridge']);
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('device.fridge.model_number')
                ->label('Fridge')
                ->searchable(),

            Tables\Columns\TextColumn::make('rom_address')
                ->label('ROM Address')
                ->searchable(),

            Tables\Columns\TextColumn::make('position'),

            Tables\Columns\TextColumn::make('temperatureLatest.temperature')
                ->label('Temperature')
                ->formatStateUsing(fn ($state) => $state ? $state . ' °F' : 'N/A')
                ->badge()
                ->color(function ($record) {
                    $temp = $record->temperatureLatest->temperature ?? null;

                    if (!$temp) return 'gray';

                    if ($temp > $record->max_temp) return 'danger';
                    if ($temp < $record->min_temp) return 'info';

                    return 'success';
                }),

            Tables\Columns\TextColumn::make('status')
                ->label('Status')
                ->getStateUsing(function ($record) {
                    $temp = $record->temperatureLatest->temperature ?? null;

                    if (!$temp) return 'N/A';

                    if ($temp > $record->max_temp) return 'High';
                    if ($temp < $record->min_temp) return 'Low';

                    return 'Normal';
                })
                ->badge()
                ->color(function ($record) {
                    $temp = $record->temperatureLatest->temperature ?? null;

                    if (!$temp) return 'gray';

                    if ($temp > $record->max_temp) return 'danger';
                    if ($temp < $record->min_temp) return 'info';

                    return 'success';
                }),
        ];
    }
}