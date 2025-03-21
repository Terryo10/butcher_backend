<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Basic product details section
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Product Name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('subcategory_id')
                            ->label('Subcategory')
                            ->relationship('subcategory', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->name} ({$record->category->name})")
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\FileUpload::make('image')
                            ->label('Main Product Image')
                            ->directory('products')
                            ->image()
                            ->required(),

                        Forms\Components\FileUpload::make('images')
                            ->label('Additional Product Images')
                            ->multiple()
                            ->directory('products')
                            ->image(),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(5)
                            ->required(),
                    ])
                    ->columns(2),

                // Pricing and quantity section
                Forms\Components\Section::make('Pricing & Quantity')
                    ->schema([
                        Forms\Components\Select::make('pricing_type')
                            ->label('Pricing Type')
                            ->options([
                                'fixed' => 'Fixed Price (per item)',
                                'weight' => 'Weight-based Price',
                            ])
                            ->default('fixed')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set) => $set('unit', null)),

                        Forms\Components\TextInput::make('price')
                            ->label('Price')
                            ->numeric()
                            ->required()
                            ->suffix('USD')
                            ->rules('numeric|min:0.01|max:10000'),

                        Forms\Components\TextInput::make('stock')
                            ->label('Stock')
                            ->numeric()
                            ->required(),

                        Forms\Components\Select::make('unit')
                            ->label('Unit')
                            ->options(function (callable $get) {
                                if ($get('pricing_type') === 'weight') {
                                    return [
                                        'kg' => 'Kilogram (kg)',
                                        'g' => 'Gram (g)',
                                        'lb' => 'Pound (lb)',
                                        'oz' => 'Ounce (oz)',
                                    ];
                                }

                                return [
                                    'piece' => 'Piece',
                                    'box' => 'Box',
                                    'pack' => 'Pack',
                                    'pair' => 'Pair',
                                    'set' => 'Set',
                                ];
                            })
                            ->required()
                            ->visible(fn (callable $get) => !is_null($get('pricing_type'))),
                    ])
                    ->columns(2),

                // Weight-based product options (conditionally visible)
                Forms\Components\Section::make('Weight-based Options')
                    ->schema([
                        Forms\Components\TextInput::make('weight')
                            ->label('Weight per Unit')
                            ->helperText('The weight of a single unit of this product')
                            ->numeric()
                            ->rules('nullable|numeric|min:0.01'),

                        Forms\Components\TextInput::make('min_quantity')
                            ->label('Minimum Order Quantity')
                            ->helperText('The minimum amount that can be ordered')
                            ->numeric()
                            ->rules('nullable|numeric|min:0.01'),

                        Forms\Components\TextInput::make('max_quantity')
                            ->label('Maximum Order Quantity')
                            ->helperText('The maximum amount that can be ordered (leave empty for no limit)')
                            ->numeric()
                            ->rules('nullable|numeric|min:0.01'),

                        Forms\Components\TextInput::make('increment')
                            ->label('Quantity Increment')
                            ->helperText('The step size for increasing/decreasing quantity (e.g., 0.1 kg)')
                            ->numeric()
                            ->rules('nullable|numeric|min:0.01'),
                    ])
                    ->columns(2)
                    ->visible(fn (callable $get) => $get('pricing_type') === 'weight'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Product Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->money('usd')
                    ->sortable(),

                Tables\Columns\TextColumn::make('pricing_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'fixed' => 'success',
                        'weight' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('unit')
                    ->label('Unit'),

                Tables\Columns\TextColumn::make('stock')
                    ->label('Stock')
                    ->sortable(),

                Tables\Columns\ImageColumn::make('image')
                    ->label('Image')
                    ->disk('public')
                    ->height(50)
                    ->width(50),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(50),

                Tables\Columns\TextColumn::make('subcategory.category.name')
                    ->label('Category')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('subcategory.name')
                    ->label('Subcategory')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('pricing_type')
                    ->options([
                        'fixed' => 'Fixed Price',
                        'weight' => 'Weight-based',
                    ])
                    ->label('Pricing Type'),

                Tables\Filters\SelectFilter::make('subcategory_id')
                    ->relationship('subcategory', 'name')
                    ->label('Subcategory'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
