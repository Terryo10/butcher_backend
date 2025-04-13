<?php

namespace App\Filament\Resources\DriverApplicationResource\Pages;

use App\Filament\Resources\DriverApplicationResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListDriverApplications extends ListRecords
{
    protected static string $resource = DriverApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
