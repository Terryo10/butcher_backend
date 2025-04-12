<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Payments';

    protected static ?string $navigationLabel = 'Transactions';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Select::make('order_id')
                            ->relationship('order', 'order_number')
                            ->required()
                            ->searchable(),

                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->required()
                            ->searchable(),

                        Forms\Components\Select::make('type')
                            ->options([
                                'ecocash' => 'Ecocash',
                                'card' => 'Credit Card',
                                'paypal' => 'PayPal',
                                'cash' => 'Cash on Delivery',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('total')
                            ->numeric()
                            ->required(),

                        Forms\Components\TextInput::make('poll_url')
                            ->maxLength(255),

                        Forms\Components\Toggle::make('isPaid')
                            ->label('Paid')
                            ->default(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),

                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('Order Number')
                    ->searchable()
                    ->url(fn (Transaction $record) => $record->order ?
                        "/admin/orders/{$record->order->id}" : null),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'ecocash' => 'Ecocash',
                        'card' => 'Credit Card',
                        'paypal' => 'PayPal',
                        'cash' => 'Cash on Delivery',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'ecocash' => 'success',
                        'card' => 'primary',
                        'paypal' => 'info',
                        'cash' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('total')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\IconColumn::make('isPaid')
                    ->label('Paid')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'ecocash' => 'Ecocash',
                        'card' => 'Credit Card',
                        'paypal' => 'PayPal',
                        'cash' => 'Cash on Delivery',
                    ]),

                Tables\Filters\Filter::make('isPaid')
                    ->query(fn (Builder $query): Builder => $query->where('isPaid', true))
                    ->label('Paid Only'),

                Tables\Filters\Filter::make('notPaid')
                    ->query(fn (Builder $query): Builder => $query->where('isPaid', false))
                    ->label('Unpaid Only'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('check_payment')
                    ->label('Check Payment')
                    ->icon('heroicon-o-arrow-path')
                    ->color('primary')
                    ->action(function (Transaction $record) {
                        // This will use your Controller's method to check payment status
                        if (!$record->poll_url) {
                            return;
                        }

                        $controller = app(\App\Http\Controllers\CheckoutController::class);

                        // The poll method from your Controller example
                        if (method_exists($controller, 'paynow')) {
                            $status = $controller->paynow($record->id, "ecocash")
                                ->pollTransaction($record->poll_url);

                            if ($status->paid()) {
                                $record->update(['isPaid' => true]);
                                $record->order->update([
                                    'payment_status' => 'paid',
                                    'status' => 'processing'
                                ]);

                                // Show success notification
                                \Filament\Notifications\Notification::make()
                                    ->title('Payment confirmed')
                                    ->body('The payment has been confirmed and the order has been updated.')
                                    ->success()
                                    ->send();
                            } else {
                                // Show pending notification
                                \Filament\Notifications\Notification::make()
                                    ->title('Payment still pending')
                                    ->body('The payment has not been confirmed yet.')
                                    ->warning()
                                    ->send();
                            }
                        }
                    })
                    ->visible(fn (Transaction $record): bool =>
                        $record->type == 'ecocash' &&
                        !$record->isPaid &&
                        $record->poll_url),

                Tables\Actions\Action::make('mark_as_paid')
                    ->label('Mark as Paid')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Transaction $record) {
                        $record->update(['isPaid' => true]);
                        $record->order->update([
                            'payment_status' => 'paid',
                            'status' => 'processing'
                        ]);

                        // Show success notification
                        \Filament\Notifications\Notification::make()
                            ->title('Marked as paid')
                            ->body('The transaction has been marked as paid and the order has been updated.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Transaction $record): bool => !$record->isPaid),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\BulkAction::make('mark_as_paid_bulk')
                    ->label('Mark as Paid')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(function (\Illuminate\Support\Collection $records) {
                        $records->each(function (Transaction $record) {
                            $record->update(['isPaid' => true]);
                            $record->order->update([
                                'payment_status' => 'paid',
                                'status' => 'processing'
                            ]);
                        });

                        // Show success notification
                        \Filament\Notifications\Notification::make()
                            ->title('Transactions marked as paid')
                            ->body($records->count() . ' transactions have been marked as paid.')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }
}
