<?php

namespace App\Filament\Exports;

use App\Models\Quote;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class CompleteQuoteExporter extends Exporter
{
    protected static ?string $model = Quote::class;

    protected static ?string $label = 'Dashboard Completo';

    public static function getColumns(): array
    {
        return [
            // 📋 # Cotización
            ExportColumn::make('quote_number')
                ->label('# Cotización'),

            // 💰 Costo
            ExportColumn::make('total_formatted')
                ->label('Costo')
                ->state(function ($record) {
                    return $record->currency === 'USD' 
                        ? 'USD ' . number_format($record->total, 2)
                        : 'S/ ' . number_format($record->total, 2);
                }),

            // 📅 Fecha Envío
            ExportColumn::make('sent_date')
                ->label('Fecha Envío')
                ->formatStateUsing(function ($state) {
                    return $state ? $state->format('d/m/Y') : 'Sin enviar';
                }),

            // 🏢 Área
            ExportColumn::make('request.requesting_department')
                ->label('Área'),

            // 👤 Contacto
            ExportColumn::make('destinationContact.full_name')
                ->label('Contacto'),

            // 📝 Descripción
            ExportColumn::make('service_description')
                ->label('Descripción'),

            // 📄 RFQ o ERP
            ExportColumn::make('rfq.rfq_number')
                ->label('RFQ o ERP'),

            // 📋 OC (Orden de Compra)
            ExportColumn::make('purchase_order_status')
                ->label('OC')
                ->state(function ($record) {
                    return $record->purchaseOrders->isNotEmpty() ? 'Sí' : 'No';
                }),

            // 📋 Número OC
            ExportColumn::make('purchase_order_number')
                ->label('Número OC')
                ->state(function ($record) {
                    return $record->purchaseOrders->first()?->po_number ?? '--';
                }),

            // ⚙️ Ejecutado
            ExportColumn::make('executed_status')
                ->label('Ejecutado')
                ->state(function ($record) {
                    $hasExecuted = $record->purchaseOrders()
                        ->whereHas('executedServices')
                        ->exists();
                    return $hasExecuted ? 'Sí' : 'No';
                }),

            // 📊 HES/MIGO
            ExportColumn::make('hes_status')
                ->label('HES/MIGO')
                ->state(function ($record) {
                    $hasHes = $record->purchaseOrders()
                        ->whereHas('executedServices.hes')
                        ->exists();
                    return $hasHes ? 'Sí' : 'No';
                }),

            // 📊 Número HES
            ExportColumn::make('hes_number')
                ->label('Número HES')
                ->state(function ($record) {
                    $hes = $record->purchaseOrders()
                        ->with('executedServices.hes')
                        ->get()
                        ->flatMap(fn($po) => $po->executedServices)
                        ->flatMap(fn($es) => $es->hes)
                        ->first();
                    
                    return $hes?->hes_number ?? '--';
                }),

            // 📧 Factura Enviada
            ExportColumn::make('invoice_status')
                ->label('Factura Enviada')
                ->state(function ($record) {
                    $hasInvoice = $record->purchaseOrders()
                        ->whereHas('executedServices.hes.invoices')
                        ->exists();
                    return $hasInvoice ? 'Sí' : 'No';
                }),

            // 🔢 Num Factura
            ExportColumn::make('invoice_number')
                ->label('Num Factura')
                ->state(function ($record) {
                    $invoice = $record->purchaseOrders()
                        ->with('executedServices.hes.invoices')
                        ->get()
                        ->flatMap(fn($po) => $po->executedServices)
                        ->flatMap(fn($es) => $es->hes)
                        ->flatMap(fn($hes) => $hes->invoices)
                        ->first();
                    
                    return $invoice?->invoice_number ?? '--';
                }),

            // 💳 Pagado
            ExportColumn::make('payment_status')
                ->label('Pagado')
                ->state(function ($record) {
                    $hasPaid = $record->purchaseOrders()
                        ->whereHas('executedServices.hes.invoices.payments')
                        ->exists();
                    return $hasPaid ? 'Sí' : 'No';
                }),

            // 💰 Monto Pagado
            ExportColumn::make('payment_amount')
                ->label('Monto Pagado')
                ->state(function ($record) {
                    $payment = $record->purchaseOrders()
                        ->with('executedServices.hes.invoices.payments')
                        ->get()
                        ->flatMap(fn($po) => $po->executedServices)
                        ->flatMap(fn($es) => $es->hes)
                        ->flatMap(fn($hes) => $hes->invoices)
                        ->flatMap(fn($invoice) => $invoice->payments)
                        ->first();
                    
                    return $payment ? 'S/ ' . number_format($payment->net_received_amount, 2) : '--';
                }),

            // 📝 OBSERVACIONES
            ExportColumn::make('notes')
                ->label('Observaciones'),

            // 📊 Estado Actual
            ExportColumn::make('state.name')
                ->label('Estado Actual'),

            // 📅 Fecha Creación
            ExportColumn::make('created_at')
                ->label('Fecha Creación')
                ->formatStateUsing(function ($state) {
                    return $state->format('d/m/Y H:i');
                }),

            // 📅 Fecha Última Actualización
            ExportColumn::make('updated_at')
                ->label('Última Actualización')
                ->formatStateUsing(function ($state) {
                    return $state->format('d/m/Y H:i');
                }),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Se ha completado la exportación del dashboard completo con ' . number_format($export->successful_rows) . ' cotizaciones.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' filas fallaron.';
        }

        return $body;
    }

    public function getFileName(Export $export): string
    {
        return "dashboard-completo-" . now()->format('Y-m-d-H-i-s');
    }
}