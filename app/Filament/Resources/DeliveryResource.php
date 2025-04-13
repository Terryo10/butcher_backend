<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeliveryResource\Pages;
use App\Filament\Resources\DeliveryResource\RelationManagers;
use App\Models\Delivery;
use App\Models\DeliveryStatus;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DeliveryResource extends Resource
{
    protected static ?string $model = Delivery::class;
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'Delivery Management';
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::pending()->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::pending()->count() > 0 ? 'warning' : 'success';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Order Information')
                    ->schema([
                        Forms\Components\TextInput::make('order.order_number')
                            ->label('Order Number')
                            ->disabled(),
                        Forms\Components\Select::make('order_id')
                            ->relationship('order', 'order_number')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                // Your order creation form here
                            ]),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Delivery Status')
                    ->schema([
                        Forms\Components\Select::make('delivery_status_id')
                            ->relationship('status', 'name')
                            ->preload()
                            ->required()
                            ->reactive(),
                        Forms\Components\Select::make('driver_id')
                            ->relationship('driver', 'name', fn (Builder $query) => $query->role('driver'))
                            ->searchable()
                            ->preload()
                            ->label('Assigned Driver'),
                        Forms\Components\DateTimePicker::make('assigned_at')
                            ->visible(fn (callable $get) => in_array($get('delivery_status_id'),
                                DeliveryStatus::whereIn('name', ['assigned', 'picked_up', 'in_transit', 'delivered'])->pluck('id')->toArray()
                            )),
                        Forms\Components\DateTimePicker::make('picked_up_at')
                            ->visible(fn (callable $get) => in_array($get('delivery_status_id'),
                                DeliveryStatus::whereIn('name', ['picked_up', 'in_transit', 'delivered'])->pluck('id')->toArray()
                            )),
                        Forms\Components\DateTimePicker::make('in_transit_at')
                            ->visible(fn (callable $get) => in_array($get('delivery_status_id'),
                                DeliveryStatus::whereIn('name', ['in_transit', 'delivered'])->pluck('id')->toArray()
                            )),
                        Forms\Components\DateTimePicker::make('delivered_at')
                            ->visible(fn (callable $get) => in_array($get('delivery_status_id'),
                                DeliveryStatus::whereIn('name', ['delivered'])->pluck('id')->toArray()
                            )),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('unassign_reason')
                            ->label('Unassign Reason')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('delivery_fee')
                            ->numeric()
                            ->prefix('$')
                            ->default(0),
                        Forms\Components\Repeater::make('delivery_notes')
                            ->schema([
                                Forms\Components\TextInput::make('note')
                                    ->required(),
                                Forms\Components\DateTimePicker::make('timestamp')
                                    ->default(now())
                                    ->required(),
                                Forms\Components\TextInput::make('user_id')
                                    ->label('User ID')
                                    ->default(auth()->id())
                                    ->required()
                                    ->numeric()
                                    ->hidden(),
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('Order')
                    ->sortable()
                    ->searchable()
                    ->url(fn (Delivery $record) => OrderResource::getUrl('edit', ['record' => $record->order_id])),
                Tables\Columns\TextColumn::make('order.user.name')
                    ->label('Customer')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('driver.name')
                    ->label('Driver')
                    ->sortable()
                    ->searchable()
                    ->placeholder('Unassigned'),
                Tables\Columns\BadgeColumn::make('status.name')
                    ->label('Status')
                    ->colors([
                        'danger' => 'failed',
                        'warning' => 'pending',
                        'success' => 'delivered',
                        'primary' => ['assigned', 'picked_up', 'in_transit'],
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('delivered_at')
                    ->label('Delivered')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Not delivered'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('delivery_status_id')
                    ->relationship('status', 'name')
                    ->label('Status')
                    ->multiple()
                    ->preload(),
                Tables\Filters\SelectFilter::make('driver_id')
                    ->relationship('driver', 'name')
                    ->label('Driver')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('unassigned')
                    ->query(fn (Builder $query) => $query->whereNull('driver_id'))
                    ->label('Unassigned Only'),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from'),
                        Forms\Components\DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('assign_driver')
                    ->label('Assign Driver')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('driver_id')
                            ->label('Select Driver')
                            ->options(User::role('driver')->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (Delivery $record, array $data): void {
                        $driver = User::findOrFail($data['driver_id']);
                        $record->assignDriver($driver);

                        Notification::make()
                            ->title('Driver Assigned')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Delivery $record) => $record->driver_id === null),
                Tables\Actions\Action::make('unassign_driver')
                    ->label('Unassign Driver')
                    ->icon('heroicon-o-user-minus')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Reason for unassigning')
                            ->required(),
                    ])
                    ->action(function (Delivery $record, array $data): void {
                        $record->unassignDriver($data['reason']);

                        Notification::make()
                            ->title('Driver Unassigned')
                            ->warning()
                            ->send();
                    })
                    ->visible(fn (Delivery $record) => $record->driver_id !== null),
                Tables\Actions\Action::make('update_status')
                    ->label('Update Status')
                    ->icon('heroicon-o-arrow-path')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->label('New Status')
                            ->options(DeliveryStatus::pluck('name', 'name'))
                            ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Status Update Notes'),
                    ])
                    ->action(function (Delivery $record, array $data): void {
                        $record->updateStatus($data['status'], [
                            'notes' => $data['notes'],
                        ]);

                        Notification::make()
                            ->title('Status Updated')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('assign_driver_bulk')
                    ->label('Assign Driver')
                    ->icon('heroicon-o-user-plus')
                    ->form([
                        Forms\Components\Select::make('driver_id')
                            ->label('Select Driver')
                            ->options(User::role('driver')->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (Collection $records, array $data): void {
                        $driver = User::findOrFail($data['driver_id']);

                        foreach ($records as $record) {
                            if ($record->driver_id === null) {
                                $record->assignDriver($driver);
                            }
                        }

                        Notification::make()
                            ->title('Deliveries Assigned')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Delivery Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('id')
                            ->label('Delivery ID'),
                        Infolists\Components\TextEntry::make('order.order_number')
                            ->label('Order Number')
                            ->url(fn (Delivery $record) => OrderResource::getUrl('edit', ['record' => $record->order_id])),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('delivery_fee')
                            ->label('Delivery Fee')
                            ->money('USD'),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('Status Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('status.name')
                            ->label('Current Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'assigned' => 'primary',
                                'picked_up' => 'primary',
                                'in_transit' => 'primary',
                                'delivered' => 'success',
                                'failed' => 'danger',
                                'returned' => 'danger',
                                'unassigned' => 'gray',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('driver.name')
                            ->label('Assigned Driver')
                            ->placeholder('Not assigned'),
                        Infolists\Components\TextEntry::make('assigned_at')
                            ->label('Assigned At')
                            ->dateTime()
                            ->placeholder('Not assigned'),
                        Infolists\Components\TextEntry::make('picked_up_at')
                            ->label('Picked Up At')
                            ->dateTime()
                            ->placeholder('Not picked up'),
                        Infolists\Components\TextEntry::make('in_transit_at')
                            ->label('In Transit At')
                            ->dateTime()
                            ->placeholder('Not in transit'),
                        Infolists\Components\TextEntry::make('delivered_at')
                            ->label('Delivered At')
                            ->dateTime()
                            ->placeholder('Not delivered'),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('Customer Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('order.user.name')
                            ->label('Customer Name'),
                        Infolists\Components\TextEntry::make('order.address.address_line_1')
                            ->label('Address Line 1'),
                        Infolists\Components\TextEntry::make('order.address.address_line_2')
                            ->label('Address Line 2')
                            ->placeholder('No address line 2'),
                        Infolists\Components\TextEntry::make('order.address.city')
                            ->label('City'),
                        Infolists\Components\TextEntry::make('order.address.state')
                            ->label('State/Province'),
                        Infolists\Components\TextEntry::make('order.address.postal_code')
                            ->label('Postal Code'),
                        Infolists\Components\TextEntry::make('order.address.country')
                            ->label('Country'),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('Delivery Notes')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('delivery_notes')
                            ->schema([
                                Infolists\Components\TextEntry::make('note')
                                    ->label('Note'),
                                Infolists\Components\TextEntry::make('timestamp')
                                    ->label('Time')
                                    ->dateTime(),
                                Infolists\Components\TextEntry::make('user_id')
                                    ->label('Added By')
                                    ->formatStateUsing(fn ($state) => User::find($state)?->name ?? 'Unknown'),
                            ])
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('unassign_reason')
                            ->label('Unassign Reason')
                            ->hidden(fn (Delivery $record) => $record->unassign_reason === null)
                            ->columnSpanFull(),
                    ]),
                Infolists\Components\Section::make('Delivery Activities')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('activities')
                            ->schema([
                                Infolists\Components\TextEntry::make('activity_type')
                                    ->label('Activity')
                                    ->formatStateUsing(fn ($state) => ucfirst($state)),
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Time')
                                    ->dateTime(),
                                Infolists\Components\TextEntry::make('user.name')
                                    ->label('User')
                                    ->placeholder('System'),
                                Infolists\Components\TextEntry::make('previous_status')
                                    ->label('From Status')
                                    ->formatStateUsing(fn ($state) => ucfirst($state))
                                    ->placeholder('N/A'),
                                Infolists\Components\TextEntry::make('new_status')
                                    ->label('To Status')
                                    ->formatStateUsing(fn ($state) => ucfirst($state))
                                    ->placeholder('N/A'),
                                Infolists\Components\TextEntry::make('notes')
                                    ->label('Notes')
                                    ->placeholder('No notes'),
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ActivitiesRelationManager::class,
            RelationManagers\NotificationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeliveries::route('/'),
            'create' => Pages\CreateDelivery::route('/create'),
            'edit' => Pages\EditDelivery::route('/{record}/edit'),
            'view' => Pages\ViewDelivery::route('/{record}'),
        ];
    }
}
