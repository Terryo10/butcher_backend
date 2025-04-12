<?php

namespace App\Filament\Resources\EcocashKeyResource\Pages;

use App\Filament\Resources\EcocashKeyResource;
use App\Models\EcocashKey;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateEcocashKey extends CreateRecord
{
    protected static string $resource = EcocashKeyResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        // If this key is marked as active, deactivate all other keys
        if ($data['is_active']) {
            EcocashKey::where('is_active', true)->update(['is_active' => false]);
        }

        return static::getModel()::create($data);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Ecocash Key created successfully';
    }
}
