<?php

namespace App\Filament\Resources\DriverApplicationResource\Pages;

use App\Filament\Resources\DriverApplicationResource;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;
use Filament\Notifications\Notification; // Add this import

class ViewDriverApplication extends ViewRecord
{
    protected static string $resource = DriverApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check')
                ->color('success')
                ->form([
                    Textarea::make('admin_notes')
                        ->label('Approval Notes')
                        ->placeholder('Optional notes about this approval'),
                ])
                ->action(function (array $data): void {
                    $this->record->approve(auth()->id(), $data['admin_notes'] ?? null);

                    // Create notification for the user
                    $this->record->user->deliveryNotifications()->create([
                        'type' => 'application_approved',
                        'title' => 'Application Approved',
                        'body' => 'Your driver application has been approved. You can now log in and start accepting deliveries.',
                        'data' => [
                            'application_id' => $this->record->id,
                        ],
                    ]);

                    // Update the user's role to 'driver'
                    $this->record->user->assignRole('driver');

                    Notification::make()
                        ->title('Application Approved')
                        ->success()
                        ->send();

                    $this->refreshFormData([
                        'status',
                        'reviewed_by',
                        'reviewed_at',
                        'admin_notes',
                    ]);
                })
                ->visible(fn () => $this->record->status === 'pending'),
            Actions\Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->form([
                    Textarea::make('admin_notes')
                        ->label('Rejection Reason')
                        ->placeholder('Please provide a reason for rejection')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->record->reject(auth()->id(), $data['admin_notes']);

                    // Create notification for the user
                    $this->record->user->deliveryNotifications()->create([
                        'type' => 'application_rejected',
                        'title' => 'Application Rejected',
                        'body' => 'Your driver application has been rejected. Reason: ' . $data['admin_notes'],
                        'data' => [
                            'application_id' => $this->record->id,
                            'reason' => $data['admin_notes'],
                        ],
                    ]);

                    Notification::make()
                        ->title('Application Rejected')
                        ->danger()
                        ->send();

                    $this->refreshFormData([
                        'status',
                        'reviewed_by',
                        'reviewed_at',
                        'admin_notes',
                    ]);
                })
                ->visible(fn () => $this->record->status === 'pending'),
        ];
    }
}
