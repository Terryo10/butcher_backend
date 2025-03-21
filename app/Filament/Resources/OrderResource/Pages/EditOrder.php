<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms;


class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),

            Actions\Action::make('back')
                ->label('Back to List')
                ->url(fn (): string => $this->getResource()::getUrl())
                ->color('secondary'),

            Actions\Action::make('update_status')
                ->label('Update Status')
                ->action(function (array $data): void {
                    $this->record->update([
                        'status' => $data['status'],
                    ]);

                    // Call the notification function
                    OrderResource::sendOrderStatusNotification($this->record);

                    $this->notify('success', 'Order status updated successfully');
                })
                ->form([
                    Forms\Components\Select::make('status')
                        ->options([
                            'pending' => 'Pending',
                            'processing' => 'Processing',
                            'shipped' => 'Shipped',
                            'delivered' => 'Delivered',
                            'cancelled' => 'Cancelled',
                        ])
                        ->required(),
                ]),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Optional: Add logic to handle status changes before saving
        // For example, you could record status history here

        return $data;
    }

    protected function afterSave(): void
    {
        // Optional: Additional logic after saving the order
        // For example, you might want to trigger status change notifications

        // Check if the status has changed
        if ($this->record->isDirty('status')) {
            OrderResource::sendOrderStatusNotification($this->record);
        }
    }
}
