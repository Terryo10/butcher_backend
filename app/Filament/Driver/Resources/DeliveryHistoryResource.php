<?php

namespace App\Filament\Driver\Resources;

use App\Filament\Driver\Resources\DeliveryHistoryResource\Pages;
use App\Models\Delivery;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;

class DeliveryHistoryResource extends Resource
{
    protected static ?string $model = Delivery::class;
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'Delivery History';
    protected static ?string $modelLabel = 'Delivery History';
    protected static ?string $pluralModelLabel = 'Delivery History';
    protected static ?string $slug = 'delivery-history';
    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->forDriver(auth()->id())
            ->whereHas('status', function (Builder $query) {
                $query->whereIn('name', ['delivered', 'failed', 'returned']);
            })
            ->latest('delivered_at');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Not needed for history view
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('Order')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('order.user.name')
                    ->label('Customer')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('status.name')
                    ->label('Status')
                    ->colors([
                        'danger' => ['failed', 'returned'],
                        'success' => 'delivered',
                    ]),
                Tables\Columns\TextColumn::make('delivery_fee')
                    ->label('Fee')
                    ->money('USD'),
                Tables\Columns\TextColumn::make('delivered_at')
                    ->label('Delivered')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->relationship('status', 'name')
                    ->options([
                        'delivered' => 'Delivered',
                        'failed' => 'Failed',
                        'returned' => 'Returned',
                    ])
                    ->multiple(),
                Tables\Filters\Filter::make('delivered_at')
                    ->form([
                        Forms\Components\DatePicker::make('delivered_from'),
                        Forms\Components\DatePicker::make('delivered_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['delivered_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('delivered_at', '>=', $date),
                            )
                            ->when(
                                $data['delivered_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('delivered_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                //
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
                            ->label('Order Number'),
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
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'delivered' => 'success',
                                'failed' => 'danger',
                                'returned' => 'danger',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('assigned_at')
                            ->label('Assigned At')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('picked_up_at')
                            ->label('Picked Up At')
                            ->dateTime()
                            ->placeholder('Not recorded'),
                        Infolists\Components\TextEntry::make('in_transit_at')
                            ->label('In Transit At')
                            ->dateTime()
                            ->placeholder('Not recorded'),
                        Infolists\Components\TextEntry::make('delivered_at')
                            ->label('Delivered At')
                            ->dateTime()
                            ->placeholder('Not recorded'),
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
                            ])
                            ->columnSpanFull(),
                    ]),
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
            'index' => Pages\ListDeliveryHistory::route('/'),
            'view' => Pages\ViewDeliveryHistory::route('/{record}'),
        ];
    }
}
