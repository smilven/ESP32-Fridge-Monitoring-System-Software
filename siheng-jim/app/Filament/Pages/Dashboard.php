<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\SystemStats;
use App\Filament\Widgets\TemperatureChart;
use App\Filament\Widgets\ActiveAlerts;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected function getHeaderWidgets(): array
    {
        return [
            SystemStats::class,
        ];
    }

    public function getWidgets(): array
    {
        return [
            TemperatureChart::class,
            ActiveAlerts::class,
        ];
    }
}