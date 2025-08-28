<?php

namespace App\Filament\Resources\ExecutedServiceResource\Pages;

use App\Filament\Resources\ExecutedServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExecutedServices extends ListRecords
{
    protected static string $resource = ExecutedServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}