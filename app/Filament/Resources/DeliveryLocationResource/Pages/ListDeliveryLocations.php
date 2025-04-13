<?php

namespace App\Filament\Resources\DeliveryLocationResource\Pages;

use App\Filament\Resources\DeliveryLocationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDeliveryLocations extends ListRecords
{
    protected static string $resource = DeliveryLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
