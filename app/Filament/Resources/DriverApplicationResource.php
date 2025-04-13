<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DriverApplicationResource\Pages;
use App\Models\DriverApplication;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;


class DriverApplicationResource extends Resource
{
    protected static ?string $model = DriverApplication::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-plus';
    protected static ?string $navigationGroup = 'Delivery Management';
    protected static ?int $navigationSort = 2;
    protected static ?string $recordTitleAttribute = 'id';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() > 0 ? 'warning' : 'success';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Applicant Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                            ])
                            ->default('pending')
                            ->required(),
                        Forms\Components\Select::make('vehicle_type')
                            ->options([
                                'car' => 'Car',
                                'motorcycle' => 'Motorcycle',
                                'bicycle' => 'Bicycle',
                                'walking' => 'Walking',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('vehicle_license_plate')
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Application Details')
                    ->schema([
                        Forms\Components\Textarea::make('application_reason')
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('id_document')
                            ->label('ID Document')
                            ->image()
                            ->directory('driver-documents/id')
                            ->visibility('private')
                            ->maxSize(5120) // 5MB
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('driving_license')
                            ->label('Driving License')
                            ->image()
                            ->directory('driver-documents/license')
                            ->visibility('private')
                            ->maxSize(5120) // 5MB
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('profile_photo')
                            ->label('Profile Photo')
                            ->image()
                            ->directory('driver-documents/photo')
                            ->visibility('private')
                            ->maxSize(5120) // 5MB
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Section::make('Review Information')
                    ->schema([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Admin Notes')
                            ->columnSpanFull(),
                        Forms\Components\Select::make('reviewed_by')
                            ->relationship('reviewer', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\DateTimePicker::make('reviewed_at'),
                    ])
                    ->columns(2)
                    ->hidden(fn (Forms\Get $get) => $get('status') === 'pending'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Applicant')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ]),
                Tables\Columns\TextColumn::make('vehicle_type')
                    ->label('Vehicle Type')
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Applied At')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reviewed_at')
                    ->label('Reviewed At')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Not reviewed'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->default('pending'),
                Tables\Filters\SelectFilter::make('vehicle_type')
                    ->options([
                        'car' => 'Car',
                        'motorcycle' => 'Motorcycle',
                        'bicycle' => 'Bicycle',
                        'walking' => 'Walking',
                    ]),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('applied_from'),
                        Forms\Components\DatePicker::make('applied_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['applied_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['applied_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->form([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Approval Notes')
                            ->placeholder('Optional notes about this approval'),
                    ])
                    ->action(function (DriverApplication $record, array $data): void {
                        $record->approve(auth()->id(), $data['admin_notes'] ?? null);

                        // Create notification for the user
                        $record->user->deliveryNotifications()->create([
                            'type' => 'application_approved',
                            'title' => 'Application Approved',
                            'body' => 'Your driver application has been approved. You can now log in and start accepting deliveries.',
                            'data' => [
                                'application_id' => $record->id,
                            ],
                        ]);

                        Notification::make()
                            ->title('Application Approved')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (DriverApplication $record) => $record->status === 'pending'),
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Rejection Reason')
                            ->placeholder('Please provide a reason for rejection')
                            ->required(),
                    ])
                    ->action(function (DriverApplication $record, array $data): void {
                        $record->reject(auth()->id(), $data['admin_notes']);

                        // Create notification for the user
                        $record->user->deliveryNotifications()->create([
                            'type' => 'application_rejected',
                            'title' => 'Application Rejected',
                            'body' => 'Your driver application has been rejected. Reason: ' . $data['admin_notes'],
                            'data' => [
                                'application_id' => $record->id,
                                'reason' => $data['admin_notes'],
                            ],
                        ]);

                        Notification::make()
                            ->title('Application Rejected')
                            ->danger()
                            ->send();
                    })
                    ->visible(fn (DriverApplication $record) => $record->status === 'pending'),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Application Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('id')
                            ->label('Application ID'),
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Applicant Name'),
                        Infolists\Components\TextEntry::make('user.email')
                            ->label('Email'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Applied At')
                            ->dateTime(),
                        TextEntry::make('status')
                            ->badge()
                            ->colors([
                                'pending' => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger',
                            ])
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('Vehicle Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('vehicle_type')
                            ->label('Vehicle Type')
                            ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                        Infolists\Components\TextEntry::make('vehicle_license_plate')
                            ->label('License Plate')
                            ->placeholder('No license plate provided'),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('Application Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('application_reason')
                            ->label('Application Reason')
                            ->columnSpanFull(),
                    ]),
                Infolists\Components\Section::make('Documents')
                    ->schema([
                        Infolists\Components\ImageEntry::make('id_document')
                            ->label('ID Document')
                            ->visibility('private')
                            ->columnSpanFull(),
                        Infolists\Components\ImageEntry::make('driving_license')
                            ->label('Driving License')
                            ->visibility('private')
                            ->columnSpanFull(),
                        Infolists\Components\ImageEntry::make('profile_photo')
                            ->label('Profile Photo')
                            ->visibility('private')
                            ->columnSpanFull(),
                    ]),
                Infolists\Components\Section::make('Review Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('reviewer.name')
                            ->label('Reviewed By')
                            ->placeholder('Not reviewed yet'),
                        Infolists\Components\TextEntry::make('reviewed_at')
                            ->label('Reviewed At')
                            ->dateTime()
                            ->placeholder('Not reviewed yet'),
                        Infolists\Components\TextEntry::make('admin_notes')
                            ->label('Admin Notes')
                            ->columnSpanFull()
                            ->placeholder('No notes provided'),
                    ])
                    ->columns(2)
                    ->hidden(fn (DriverApplication $record) => $record->status === 'pending'),
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
            'index' => Pages\ListDriverApplications::route('/'),
            'create' => Pages\CreateDriverApplication::route('/create'),
            'edit' => Pages\EditDriverApplication::route('/{record}/edit'),
            'view' => Pages\ViewDriverApplication::route('/{record}'),
        ];
    }
}
