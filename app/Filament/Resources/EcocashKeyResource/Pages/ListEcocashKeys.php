<?php

namespace App\Filament\Resources\EcocashKeyResource\Pages;

use App\Filament\Resources\EcocashKeyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEcocashKeys extends ListRecords
{
    protected static string $resource = EcocashKeyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
