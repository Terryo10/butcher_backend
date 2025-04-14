<?php

namespace App\Filament\Resources\UserResource\Actions;

use Filament\Forms\Components\Select;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class QuickRoleAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Change Role')
            ->icon('heroicon-o-user-group')
            ->color('warning')
            ->modalHeading('Change User Role')
            ->modalDescription('Select the new role for this user')
            ->form([
                Select::make('role')
                    ->label('Role')
                    ->options(Role::pluck('name', 'name'))
                    ->required(),
            ])
            ->action(function (array $data, Model $record): void {
                $record->syncRoles([$data['role']]);
                $this->success();
            })
            ->successNotificationTitle('User role changed successfully')
            ->requiresConfirmation();
    }
}
