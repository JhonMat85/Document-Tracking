<?php

namespace App\Filament\Resources\QuoteResource\Pages;

use App\Filament\Resources\QuoteResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQuote extends CreateRecord
{
    protected static string $resource = QuoteResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generar número de cotización automático
        $year = date('Y');
        $lastQuote = \App\Models\Quote::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();
        
        $nextNumber = $lastQuote ? (int)substr($lastQuote->quote_number, -4) + 1 : 1;
        $data['quote_number'] = 'COT-' . $year . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        
        $data['created_by'] = auth()->id();
        
        return $data;
    }
}