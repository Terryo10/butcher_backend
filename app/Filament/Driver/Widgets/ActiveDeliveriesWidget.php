<?php

namespace App\Filament\Driver\Widgets;

use App\Models\Delivery;
use App\Models\DeliveryStatus;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Filament\Forms;

class ActiveDeliveriesWidget extends BaseWidget
{
    protected static ?string $heading = 'Your Active Deliveries';
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $pollingInterval = '15s';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Delivery::query()
                    ->forDriver(auth()->id())
                    ->inProgress()
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('Order')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('order.user.name')
                    ->label('Customer')
                    ->searchable(),
                Tables\Columns\TextColumn::make('order.address.full_address')
                    ->label('Delivery Address')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\BadgeColumn::make('status.name')
                    ->label('Status')
                    ->colors([
                        'primary' => 'assigned',
                        'warning' => 'picked_up',
                        'success' => 'in_transit',
                    ]),
                Tables\Columns\TextColumn::make('delivery_fee')
                    ->label('Fee')
                    ->money('USD'),
                Tables\Columns\TextColumn::make('assigned_at')
                    ->label('Assigned')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('update_status')
                    ->label('Update Status')
                    ->icon('heroicon-o-arrow-path')
                    ->form(function (Delivery $record) {
                        $statusOptions = [];
                        $currentStatus = $record->status->name;

                        // Define valid status transitions
                        if ($currentStatus === 'assigned') {
                            $statusOptions['picked_up'] = 'Picked Up';
                        } elseif ($currentStatus === 'picked_up') {
                            $statusOptions['in_transit'] = 'In Transit';
                        } elseif ($currentStatus === 'in_transit') {
                            $statusOptions['delivered'] = 'Delivered';
                        }

                        return [
                            Forms\Components\Select::make('status')
                                ->label('New Status')
                                ->options($statusOptions)
                                ->required(),
                            Forms\Components\Textarea::make('notes')
                                ->label('Status Update Notes'),
                            // Optional: Add location capture
                            Forms\Components\Hidden::make('latitude'),
                            Forms\Components\Hidden::make('longitude'),
                        ];
                    })
                    ->action(function (Delivery $record, array $data): void {
                        // Build additional data array
                        $additionalData = [];

                        if (!empty($data['notes'])) {
                            $additionalData['notes'] = $data['notes'];
                        }

                        // Add location if available
                        if (!empty($data['latitude']) && !empty($data['longitude'])) {
                            $additionalData['location'] = [
                                'latitude' => $data['latitude'],
                                'longitude' => $data['longitude'],
                            ];
                        }

                        // If delivering, potentially capture signature
                        if ($data['status'] === 'delivered') {
                            // Handle signature if provided
                            if (!empty($data['signature'])) {
                                $additionalData['signature'] = $data['signature'];
                            }
                        }

                        $record->updateStatus($data['status'], $additionalData);

                        Notification::make()
                            ->title('Status Updated')
                            ->success()
                            ->send();

                        // Refresh the table
                        $this->dispatch('delivery-updated');
                    }),
                Tables\Actions\Action::make('unassign')
                    ->label('Unassign')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Reason for unassigning')
                            ->required(),
                    ])
                    ->action(function (Delivery $record, array $data): void {
                        $record->unassignDriver($data['reason']);

                        Notification::make()
                            ->title('Delivery Unassigned')
                            ->warning()
                            ->send();

                        // Refresh the table
                        $this->dispatch('delivery-updated');
                    }),
            ])
            ->emptyStateHeading('No Active Deliveries')
            ->emptyStateDescription('You have no active deliveries right now. Check the available deliveries section to pick up some work!')
            ->emptyStateIcon('heroicon-o-truck')
            ->poll('15s');
    }
}
