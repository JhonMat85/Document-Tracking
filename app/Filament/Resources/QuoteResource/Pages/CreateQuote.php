<?php

namespace App\Filament\Resources\QuoteResource\Pages;

use App\Filament\Resources\QuoteResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQuote extends CreateRecord
{
    protected static string $resource = QuoteResource::class;

        protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generar número de cotización automático usando el método del modelo
        $data['quote_number'] = \App\Models\Quote::generateQuoteNumber();
        $data['created_by'] = auth()->id();

        return $data;
    }
}