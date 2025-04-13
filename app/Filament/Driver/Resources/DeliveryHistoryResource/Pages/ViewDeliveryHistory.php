<?php

namespace App\Filament\Driver\Resources\DeliveryHistoryResource\Pages;

use App\Filament\Driver\Resources\DeliveryHistoryResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;

class ViewDeliveryHistory extends ViewRecord
{
    protected static string $resource = DeliveryHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
