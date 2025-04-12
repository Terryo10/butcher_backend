<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EcocashKeyResource\Pages;
use App\Models\EcocashKey;
use Filament\Forms;

use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class EcocashKeyResource extends Resource
{
    protected static ?string $model = EcocashKey::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'Payments';

    protected static ?string $navigationLabel = 'Ecocash Keys';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('integration_id')
                            ->required()
                            ->maxLength(255)
                            ->label('PayNow Integration ID'),

                        Forms\Components\TextInput::make('integration_key')
                            ->required()
                            ->maxLength(255)
                            ->label('PayNow Integration Key'),

                        Forms\Components\TextInput::make('return_url')
                            ->required()
                            ->maxLength(255)
                            ->label('Return URL')
                            ->helperText('User will be redirected to this URL after payment'),

                        Forms\Components\TextInput::make('result_url')
                            ->required()
                            ->maxLength(255)
                            ->label('Result URL')
                            ->helperText('PayNow will send payment notifications to this URL'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->helperText('Only one integration can be active at a time')
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('integration_id')
                    ->label('Integration ID')
                    ->searchable(),

                Tables\Columns\TextColumn::make('return_url')
                    ->label('Return URL')
                    ->limit(30),

                Tables\Columns\TextColumn::make('result_url')
                    ->label('Result URL')
                    ->limit(30),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

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
                Tables\Filters\Filter::make('is_active')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true))
                    ->label('Active Only'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('activate')
                    ->label('Set as Active')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function (EcocashKey $record): void {
                        // Deactivate all other keys
                        EcocashKey::where('id', '!=', $record->id)
                            ->update(['is_active' => false]);

                        // Activate this key
                        $record->update(['is_active' => true]);
                    })
                    ->visible(fn (EcocashKey $record): bool => !$record->is_active),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListEcocashKeys::route('/'),
            'create' => Pages\CreateEcocashKey::route('/create'),
            'edit' => Pages\EditEcocashKey::route('/{record}/edit'),
        ];
    }
}
