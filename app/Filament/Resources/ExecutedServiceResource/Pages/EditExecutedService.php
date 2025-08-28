<?php

namespace App\Filament\Resources\ExecutedServiceResource\Pages;

use App\Filament\Resources\ExecutedServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExecutedService extends EditRecord
{
    protected static string $resource = ExecutedServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();
        
        return $data;
    }
}