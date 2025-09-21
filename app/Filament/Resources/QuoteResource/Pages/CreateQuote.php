<?php

namespace App\Filament\Resources\QuoteResource\Pages;

use App\Filament\Resources\QuoteResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

class CreateQuote extends CreateRecord
{
    protected static string $resource = QuoteResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        // Log para debug de los datos del formulario
        Log::info('CREATE QUOTE DEBUG - Form data before create', [
            'data' => $data,
            'details_count' => isset($data['details']) ? count($data['details']) : 0
        ]);

        return $data;
    }
}