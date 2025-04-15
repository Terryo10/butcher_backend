<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeliveryLocationResource\Pages;
use App\Models\DeliveryLocation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DeliveryLocationResource extends Resource
{
    protected static ?string $model = DeliveryLocation::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationGroup = 'Shop Settings';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('location_name')
                            ->required()
                            ->maxLength(255)
                            ->label('Location Name'),
                        Forms\Components\TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->minValue(0)
                            ->label('Delivery Price'),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('location_name')
                    ->searchable()
                    ->sortable()
                    ->label('Location Name'),
                Tables\Columns\TextColumn::make('price')
                    ->money('USD')
                    ->sortable()
                    ->label('Delivery Price'),
                Tables\Columns\TextColumn::make('addresses_count')
                    ->counts('addresses')
                    ->label('Addresses'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (DeliveryLocation $record, Tables\Actions\DeleteAction $action) {
                        // Check if location is in use by any addresses
                        if ($record->addresses()->count() > 0) {
                            $action->cancel();
                            $action->halt('Cannot delete this location as it is being used by one or more addresses.');
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records, Tables\Actions\DeleteBulkAction $action) {
                            // Check if any locations are in use
                            foreach ($records as $record) {
                                if ($record->addresses()->count() > 0) {
                                    $action->cancel();
                                    $action->halt('Cannot delete some locations as they are being used by addresses.');
                                    break;
                                }
                            }
                        }),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
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
            'index' => Pages\ListDeliveryLocations::route('/'),
            'create' => Pages\CreateDeliveryLocation::route('/create'),
            'edit' => Pages\EditDeliveryLocation::route('/{record}/edit'),
        ];
    }
}
