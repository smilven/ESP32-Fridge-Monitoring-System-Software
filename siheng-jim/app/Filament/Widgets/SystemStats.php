<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Device;
use App\Models\Alert;
use App\Models\Branch;
use App\Models\Fridge;

class SystemStats extends StatsOverviewWidget
{


    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
    return [

        Stat::make('Branches', Branch::count())
            ->color('primary')
            ->icon('heroicon-o-building-storefront')
            ->description('Total Branches')
            ->columnSpan(2),

        Stat::make('Fridges', Fridge::count())
            ->color('success')
            ->icon('heroicon-o-archive-box')
            ->description('Total Fridges')
            ->columnSpan(2),

        Stat::make('Total Devices', Device::count())
            ->color('gray')
            ->icon('heroicon-o-cpu-chip'),

        Stat::make('Total Online Devices', Device::where('status','online')->count())
            ->color('success')
            ->icon('heroicon-o-check-circle'),

        Stat::make('Total Offline Devices', Device::where('status','offline')->count())
            ->color('danger')
            ->icon('heroicon-o-x-circle'),

        Stat::make('Total Active Alerts', Alert::where('status','active')->count())
            ->color('warning')
            ->icon('heroicon-o-exclamation-triangle'),

    ];
}
}