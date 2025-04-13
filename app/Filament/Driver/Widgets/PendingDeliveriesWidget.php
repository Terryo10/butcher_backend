<?php

namespace App\Filament\Driver\Widgets;

use App\Models\Delivery;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Filament\Forms;

class PendingDeliveriesWidget extends BaseWidget
{
    protected static ?string $heading = 'Available Deliveries';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $pollingInterval = '15s';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Delivery::query()
                    ->pending()
                    ->orderBy('created_at')
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
                Tables\Columns\TextColumn::make('delivery_fee')
                    ->label('Fee')
                    ->money('USD'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('order.delivery_priority')
                    ->label('Priority')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'high' => 'danger',
                        'normal' => 'primary',
                        'low' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('assign_self')
                    ->label('Pick Up Delivery')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(function (Delivery $record): void {
                        $record->assignDriver(auth()->user());

                        Notification::make()
                            ->title('Delivery Assigned')
                            ->success()
                            ->body('You have successfully picked up this delivery.')
                            ->send();

                        // Refresh the table
                        $this->dispatch('delivery-updated');
                    }),
            ])
            ->emptyStateHeading('No Available Deliveries')
            ->emptyStateDescription('There are no available deliveries at the moment. Check back later!')
            ->emptyStateIcon('heroicon-o-clipboard-document-check')
            ->poll('15s');
    }
}
