<?php

namespace App\Filament\Resources\HesResource\Pages;

use App\Filament\Resources\HesResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHes extends EditRecord
{
    protected static string $resource = HesResource::class;

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