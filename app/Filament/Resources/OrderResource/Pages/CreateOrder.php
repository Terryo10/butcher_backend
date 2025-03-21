<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate order number if not provided
        if (empty($data['order_number'])) {
            $data['order_number'] = \App\Models\Order::generateOrderNumber();
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        // Any logic needed after creating a new order
    }
}
