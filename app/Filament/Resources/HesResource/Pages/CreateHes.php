<?php

namespace App\Filament\Resources\HesResource\Pages;

use App\Filament\Resources\HesResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHes extends CreateRecord
{
    protected static string $resource = HesResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}