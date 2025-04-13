<?php

namespace App\Filament\Driver\Widgets;

use App\Models\Delivery;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class DeliveryStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        $user = Auth::user();

        // Fetch stats for the logged-in driver
        $totalCompleted = Delivery::forDriver($user->id)->completed()->count();
        $activeDeliveries = Delivery::forDriver($user->id)->inProgress()->count();
        $earnings = Delivery::forDriver($user->id)->completed()->sum('delivery_fee');

        // Get stats for today
        $todayCompleted = Delivery::forDriver($user->id)
            ->completed()
            ->whereDate('delivered_at', now()->toDateString())
            ->count();

        $todayEarnings = Delivery::forDriver($user->id)
            ->completed()
            ->whereDate('delivered_at', now()->toDateString())
            ->sum('delivery_fee');

        return [
            Stat::make('Active Deliveries', $activeDeliveries)
                ->description('Currently assigned to you')
                ->descriptionIcon('heroicon-m-truck')
                ->color('primary'),

            Stat::make('Completed Today', $todayCompleted)
                ->description('Deliveries completed today')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Today\'s Earnings', "$" . number_format($todayEarnings, 2))
                ->description('From completed deliveries')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Total Completed', $totalCompleted)
                ->description('All time completed deliveries')
                ->descriptionIcon('heroicon-m-archive-box')
                ->color('gray'),

            Stat::make('Total Earnings', "$" . number_format($earnings, 2))
                ->description('All time earnings')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('gray'),
        ];
    }
}
