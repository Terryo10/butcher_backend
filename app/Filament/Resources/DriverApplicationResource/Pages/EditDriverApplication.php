<?php

namespace App\Filament\Resources\DriverApplicationResource\Pages;

use App\Filament\Resources\DriverApplicationResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;

class EditDriverApplication extends EditRecord
{
    protected static string $resource = DriverApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
