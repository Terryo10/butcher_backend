<?php


namespace App\Filament\Resources\DeliveryResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class NotificationsRelationManager extends RelationManager
{
    protected static string $relationship = 'notifications';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required(),
                Forms\Components\Textarea::make('body')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => ucfirst(str_replace('_', ' ', $state))),
                Tables\Columns\TextColumn::make('title')
                    ->label('Title'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime(),
                Tables\Columns\IconColumn::make('read')
                    ->boolean(),
                Tables\Columns\IconColumn::make('sent')
                    ->boolean(),
                Tables\Columns\TextColumn::make('channel')
                    ->label('Channel')
                    ->formatStateUsing(fn ($state) => ucfirst($state)),
            ])
            ->filters([
                Tables\Filters\Filter::make('unread')
                    ->query(fn ($query) => $query->where('read', false))
                    ->label('Unread Only'),
                Tables\Filters\Filter::make('unsent')
                    ->query(fn ($query) => $query->where('sent', false))
                    ->label('Unsent Only'),
            ])
            ->headerActions([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('mark_read')
                    ->label('Mark Read')
                    ->icon('heroicon-o-check')
                    ->action(fn ($record) => $record->markAsRead())
                    ->visible(fn ($record) => !$record->read),
                Tables\Actions\Action::make('mark_sent')
                    ->label('Mark Sent')
                    ->icon('heroicon-o-paper-airplane')
                    ->action(fn ($record) => $record->markAsSent())
                    ->visible(fn ($record) => !$record->sent),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('mark_read_bulk')
                    ->label('Mark as Read')
                    ->icon('heroicon-o-check')
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            $record->markAsRead();
                        }
                    }),
            ]);
    }
}
