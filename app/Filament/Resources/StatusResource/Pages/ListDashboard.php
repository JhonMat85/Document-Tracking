<?php

namespace App\Filament\Resources\StatusResource\Pages;

use App\Filament\Resources\StatusResource;
use App\Filament\Widgets\DocumentStatsWidget;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class ListDashboard extends ListRecords
{
    protected static string $resource = StatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Sin acciones de crear para mantener simplicidad
        ];
    }

    // Widgets de estadísticas en el header
    protected function getHeaderWidgets(): array
    {
        return [
            DocumentStatsWidget::class,
        ];
    }

    // Personalizar el título de la página
    public function getTitle(): string
    {
        return '📊 Dashboard de Estado de Documentos';
    }

    // Personalizar el subtítulo
    public function getSubheading(): ?string
    {
        return 'Vista general del progreso de todas las cotizaciones y documentos relacionados';
    }

    // Eager loading optimizado para máximo rendimiento
    protected function getTableQuery(): Builder|Relation|null
    {
        return static::getResource()::getEloquentQuery()
            ->select([
                'quotes.*',
                'requests.requesting_department',
                'client_contacts.full_name as contact_name',
                'rfqs.rfq_number'
            ])
            ->leftJoin('requests', 'quotes.request_id', '=', 'requests.id')
            ->leftJoin('client_contacts', 'quotes.destination_contact_id', '=', 'client_contacts.id')
            ->leftJoin('rfqs', 'quotes.rfq_id', '=', 'rfqs.id')
            ->with([
                'purchaseOrders:id,quote_id,po_number',
                'purchaseOrders.executedServices:id,purchase_order_id',
                'purchaseOrders.executedServices.hes:id,executed_service_id,hes_number',
                'purchaseOrders.executedServices.hes.invoices:id,hes_id,invoice_number',
                'purchaseOrders.executedServices.hes.invoices.payments:id,invoice_id'
            ])
            ->orderBy('quotes.created_at', 'desc');
    }
}