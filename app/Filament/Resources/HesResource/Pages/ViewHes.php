<?php

namespace App\Filament\Resources\HesResource\Pages;

use App\Filament\Resources\HesResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewHes extends ViewRecord
{
    protected static string $resource = HesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}