<?php

namespace App\Filament\Driver\Pages;

use App\Filament\Driver\Widgets\ActiveDeliveriesWidget;
use App\Filament\Driver\Widgets\DeliveryStatsWidget;
use App\Filament\Driver\Widgets\PendingDeliveriesWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected function getHeaderWidgets(): array
    {
        return [
            DeliveryStatsWidget::class,
        ];
    }

    public function getColumns(): int | array
    {
        return 2;
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return 1;
    }

    public function getWidgets(): array
    {
        return [
            DeliveryStatsWidget::class,
            ActiveDeliveriesWidget::class,
            PendingDeliveriesWidget::class,
        ];
    }
}
