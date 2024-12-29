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
                // Input for product name
                Forms\Components\TextInput::make('name')
                    ->label('Product Name')
                    ->required() // Make the field mandatory
                    ->maxLength(255), // Set a maximum length for validation

                // Input for product price
                Forms\Components\TextInput::make('price')
                    ->label('Price')
                    ->numeric() // Enforce numeric values
                    ->required() // Make the field mandatory
                    ->suffix('USD') // Display "USD" as a suffix
                    ->rules('numeric|min:0.01|max:10000'), // Validate the value range

                // Input for product stock
                Forms\Components\TextInput::make('stock')
                    ->label('Stock')
                    ->numeric() // Only allow numeric values
                    ->required(), // Ensure stock is provided

                // File upload for product image
                Forms\Components\FileUpload::make('image')
                    ->label('Product Image')
                    ->directory('products') // Define upload directory
                    ->image() // Restrict to images only
                    ->required(), // Make image mandatory

                // Textarea for product description
                Forms\Components\Textarea::make('description')
                    ->label('Description')
                    ->rows(5) // Set number of rows for the textarea
                    ->required(), // Ensure the field is filled

                // Dropdown select for subcategory
                Forms\Components\Select::make('subcategory_id')
                    ->label('Subcategory')
                    ->relationship('subcategory', 'name') // Establish relationship
                    ->searchable() // Allow searching within options
                    ->required(), // Make the selection mandatory
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                            // Column for Product Name
                            Tables\Columns\TextColumn::make('name')
                                ->label('Product Name')
                                ->searchable()
                                ->sortable(),

                            // Column for Price
                            Tables\Columns\TextColumn::make('price')
                                ->label('Price')
                                ->money('usd') // Format as currency
                                ->sortable(),

                            // Column for Stock
                            Tables\Columns\TextColumn::make('stock')
                                ->label('Stock')
                                ->sortable(),

                            // Column for Product Image
                            Tables\Columns\ImageColumn::make('image')
                                ->label('Image')
                                ->disk('public') // Assuming you're storing images in a public disk
                                ->height(50) // Set a fixed height
                                ->width(50),

                            // Column for Description
                            Tables\Columns\TextColumn::make('description')
                                ->label('Description')
                                ->limit(50), // Show only first 50 characters,

            // Column for Subcategory Name
            Tables\Columns\TextColumn::make('subcategory.name')
                ->label('Subcategory')
                ->sortable()
                ->searchable(),
        ])


            ->filters([
                //
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
