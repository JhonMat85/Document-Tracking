<?php

namespace App\Filament\Resources\ExecutedServiceResource\Pages;

use App\Filament\Resources\ExecutedServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewExecutedService extends ViewRecord
{
    protected static string $resource = ExecutedServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}