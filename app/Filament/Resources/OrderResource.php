<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;
use App\Filament\Resources\OrderResource\RelationManagers;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationGroup = 'Shop';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('order_number')
                            ->required()
                            ->disabled(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'processing' => 'Processing',
                                'shipped' => 'Shipped',
                                'delivered' => 'Delivered',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required(),
                        Forms\Components\Select::make('payment_status')
                            ->options([
                                'pending' => 'Pending',
                                'paid' => 'Paid',
                                'failed' => 'Failed',
                                'refunded' => 'Refunded',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('tracking_number'),
                        Forms\Components\Textarea::make('notes')
                            ->columnSpan('full'),
                    ])
                    ->columns(2),

                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Placeholder::make('user')
                            ->content(fn (?Order $record): string => $record?->user?->name ?? 'N/A'),
                        Forms\Components\Placeholder::make('email')
                            ->content(fn (?Order $record): string => $record?->user?->email ?? 'N/A'),
                        Forms\Components\Placeholder::make('subtotal')
                            ->content(fn (?Order $record): string => $record ? '$' . number_format($record->subtotal, 2) : 'N/A'),
                        Forms\Components\Placeholder::make('shipping_amount')
                            ->content(fn (?Order $record): string => $record ? '$' . number_format($record->shipping_amount, 2) : 'N/A'),
                        Forms\Components\Placeholder::make('tax_amount')
                            ->content(fn (?Order $record): string => $record ? '$' . number_format($record->tax_amount, 2) : 'N/A'),
                        Forms\Components\Placeholder::make('discount_amount')
                            ->content(fn (?Order $record): string => $record ? '$' . number_format($record->discount_amount, 2) : 'N/A'),
                        Forms\Components\Placeholder::make('total')
                            ->content(fn (?Order $record): string => $record ? '$' . number_format($record->total, 2) : 'N/A')
                            ->columnSpan('full'),
                    ])
                    ->columns(2)
                    ->heading('Order Information')
                    ->visible(fn (?Order $record): bool => $record !== null), // Hide card when creating

                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Placeholder::make('address')
                            ->content(function (?Order $record): string {
                                if (!$record || !$record->address) {
                                    return 'No address found';
                                }

                                $address = $record->address;
                                return "{$address->address_line1}, {$address->city}, {$address->state} {$address->postal_code}";
                            })
                            ->columnSpan('full'),
                    ])
                    ->heading('Shipping Information')
                    ->visible(fn (?Order $record): bool => $record !== null), // Hide card when creating
            ]);
    }
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'primary' => 'pending',
                        'warning' => 'processing',
                        'success' => 'shipped',
                        'success' => 'delivered',
                        'danger' => 'cancelled',
                    ]),
                Tables\Columns\BadgeColumn::make('payment_status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'paid',
                        'danger' => 'failed',
                        'primary' => 'refunded',
                    ]),
                Tables\Columns\TextColumn::make('payment_type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'ecocash' => 'Ecocash',
                        'card' => 'Credit Card',
                        'paypal' => 'PayPal',
                        'cashOnDelivery' => 'Cash on Delivery',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'ecocash' => 'success',
                        'card' => 'primary',
                        'paypal' => 'info',
                        'cashOnDelivery' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('total')
                    ->money('usd'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'shipped' => 'Shipped',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('payment_status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('update_status')
                    ->label('Update Status')
                    ->icon('heroicon-o-arrow-path')
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
                    ])
                    ->action(function (Order $record, array $data): void {
                        $record->update([
                            'status' => $data['status'],
                        ]);

                        // Placeholder for notification functionality
                        self::sendOrderStatusNotification($record);

                        Notification::make()
                            ->title('Order status updated successfully')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('check_ecocash_payment')
                    ->label('Check Ecocash Payment')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Order $record): bool =>
                        $record->payment_type === 'ecocash' &&
                        $record->payment_status === 'pending' &&
                        $record->payment_reference
                    )
                    ->action(function (Order $record) {
                        try {
                            // Get the transaction if it exists
                            $transaction = $record->transaction;

                            if (!$transaction || !$transaction->poll_url) {
                                Notification::make()
                                    ->title('No payment information')
                                    ->body('No valid payment reference found for this order.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            // Check payment status
                            $controller = app(\App\Http\Controllers\Controller::class);
                            $status = $controller->paynow($transaction->id, "ecocash")
                                ->pollTransaction($transaction->poll_url);

                            if ($status->paid()) {
                                // Update transaction and order
                                $transaction->update(['isPaid' => true]);
                                $record->update([
                                    'payment_status' => 'paid',
                                    'status' => 'processing'
                                ]);

                                Notification::make()
                                    ->title('Payment confirmed')
                                    ->body('The Ecocash payment has been confirmed.')
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Payment still pending')
                                    ->body('The Ecocash payment has not been confirmed yet.')
                                    ->warning()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error checking payment')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\BulkAction::make('update_status_bulk')
                    ->label('Update Status')
                    ->icon('heroicon-o-arrow-path')
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
                    ])
                    ->action(function (Collection $records, array $data): void {
                        foreach ($records as $record) {
                            $record->update([
                                'status' => $data['status'],
                            ]);

                            // Placeholder for notification functionality
                            self::sendOrderStatusNotification($record);
                        }

                        Notification::make()
                            ->title(count($records) . ' orders updated successfully')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }

    // Empty function for future notification implementation
    protected static function sendOrderStatusNotification(Order $order): void
    {
        // This function will be implemented later for sending notifications
        // to customers when an order status changes

        // Potential implementation:
        // 1. Send email notification
        // 2. Send SMS notification
        // 3. Send push notification
        // 4. Record notification in database
    }
}
