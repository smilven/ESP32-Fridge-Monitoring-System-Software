<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\TemperatureLog;
use Illuminate\Support\Facades\DB;

class TemperatureChart extends ChartWidget
{
    protected  ?string $heading = 'Average Temperature (Last 24h)';

    protected  ?string $pollingInterval = '10s';
    protected int|string|array $columnSpan = 1;

    protected static ?int $sort = 1;
    protected function getData(): array
    {

        $data = TemperatureLog::select(
                DB::raw('AVG(temperature) as avg_temp'),
                DB::raw('DATE_FORMAT(created_at, "%H:00") as time')
            )
            ->groupBy('time')
            ->orderBy('time')
            ->take(20)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Average °F',
                    'data' => $data->pluck('avg_temp'),
                ],
            ],
            'labels' => $data->pluck('time'),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}