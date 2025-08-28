<?php

namespace App\Filament\Resources\HesResource\Pages;

use App\Filament\Resources\HesResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHes extends ListRecords
{
    protected static string $resource = HesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Registrar HES')
                ->color('success'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Aquí se puede agregar un widget de alerta para servicios sin HES
        ];
    }
}