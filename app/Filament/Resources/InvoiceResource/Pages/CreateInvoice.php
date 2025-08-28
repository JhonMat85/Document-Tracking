<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        
        // Generar número de factura automático
        $currentYear = date('Y');
        $lastInvoice = \App\Models\Invoice::whereYear('created_at', $currentYear)
            ->orderBy('invoice_number', 'desc')
            ->first();
        
        if ($lastInvoice && preg_match('/INV-' . $currentYear . '-(\d+)/', $lastInvoice->invoice_number, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        } else {
            $nextNumber = 1;
        }
        
        $data['invoice_number'] = 'INV-' . $currentYear . '-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
        
        return $data;
    }
}