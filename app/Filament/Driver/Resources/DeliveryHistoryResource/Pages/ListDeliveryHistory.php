<?php

namespace App\Filament\Driver\Resources\DeliveryHistoryResource\Pages;

use App\Filament\Driver\Resources\DeliveryHistoryResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListDeliveryHistory extends ListRecords
{
    protected static string $resource = DeliveryHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
