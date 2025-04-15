<?php

namespace App\Filament\Resources\TaxResource\Pages;

use App\Filament\Resources\TaxResource;
use App\Models\Tax;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateTax extends CreateRecord
{
    protected static string $resource = TaxResource::class;

    /**
     * Handle the record creation event.
     *
     * @param Model $record
     * @return void
     */
    protected function afterCreate(): void
    {
        // If this tax is set as default, update all other taxes
        if ($this->record->is_default) {
            Tax::where('id', '!=', $this->record->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
