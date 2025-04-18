<?php
namespace App\Filament\Resources\DeliveryResource\Pages;

use App\Filament\Resources\DeliveryResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;

class ViewDelivery extends ViewRecord
{
protected static string $resource = DeliveryResource::class;

protected function getHeaderActions(): array
{
return [
Actions\EditAction::make(),
];
}
}
