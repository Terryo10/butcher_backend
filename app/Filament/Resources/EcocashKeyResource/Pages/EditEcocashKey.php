<?php

namespace App\Filament\Resources\EcocashKeyResource\Pages;

use App\Filament\Resources\EcocashKeyResource;
use App\Models\EcocashKey;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditEcocashKey extends EditRecord
{
    protected static string $resource = EcocashKeyResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // If this key is being marked as active, handle deactivating other keys
        if ($data['is_active'] && !$this->record->is_active) {
            EcocashKey::where('id', '!=', $this->record->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        return $data;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Ecocash Key updated successfully';
    }
}
