<?php

namespace App\Filament\Resources\ExecutedServiceResource\Pages;

use App\Filament\Resources\ExecutedServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateExecutedService extends CreateRecord
{
    protected static string $resource = ExecutedServiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        
        return $data;
    }
}