<?php

namespace App\Filament\Widgets;

use App\Models\Quote;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class CompleteStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        // Obtener todas las estadísticas en una sola consulta optimizada
        $stats = Quote::select([
            DB::raw('COUNT(*) as total_quotes'),
            DB::raw('SUM(total) as total_value'),
            DB::raw('SUM(CASE WHEN currency = "USD" THEN total * 3.8 ELSE total END) as total_value_pen'),
            DB::raw('COUNT(CASE WHEN EXISTS (SELECT 1 FROM purchase_orders WHERE purchase_orders.quote_id = quotes.id) THEN 1 END) as with_po'),
            DB::raw('COUNT(CASE WHEN EXISTS (SELECT 1 FROM purchase_orders po JOIN executed_services es ON po.id = es.purchase_order_id WHERE po.quote_id = quotes.id) THEN 1 END) as executed'),
            DB::raw('COUNT(CASE WHEN EXISTS (SELECT 1 FROM purchase_orders po JOIN executed_services es ON po.id = es.purchase_order_id JOIN hes h ON es.id = h.executed_service_id WHERE po.quote_id = quotes.id) THEN 1 END) as with_hes'),
            DB::raw('COUNT(CASE WHEN EXISTS (SELECT 1 FROM purchase_orders po JOIN executed_services es ON po.id = es.purchase_order_id JOIN hes h ON es.id = h.executed_service_id JOIN invoices i ON h.id = i.hes_id WHERE po.quote_id = quotes.id) THEN 1 END) as invoiced'),
            DB::raw('COUNT(CASE WHEN EXISTS (SELECT 1 FROM purchase_orders po JOIN executed_services es ON po.id = es.purchase_order_id JOIN hes h ON es.id = h.executed_service_id JOIN invoices i ON h.id = i.hes_id JOIN payments p ON i.id = p.invoice_id WHERE po.quote_id = quotes.id) THEN 1 END) as paid'),
        ])->first();

        $pendingCount = $stats->total_quotes - $stats->executed;

        return [
            Stat::make('📋 TOTAL COTIZACIONES', number_format($stats->total_quotes))
                ->description('Total de cotizaciones en el sistema')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary')
                ->chart([1, 2, 3, 4, 5, 6, 7])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ]),

            Stat::make('💰 VALOR TOTAL', 'S/ ' . number_format($stats->total_value_pen, 2))
                ->description('Valor total en soles (USD convertido)')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->chart([10, 20, 15, 25, 30, 35, 40])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ]),

            Stat::make('📋 CON ORDEN COMPRA', number_format($stats->with_po))
                ->description('Cotizaciones con OC generada')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('info')
                ->chart([2, 4, 3, 5, 4, 6, 5])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ]),

            Stat::make('⚙️ EJECUTADOS', number_format($stats->executed))
                ->description('Servicios ejecutados')
                ->descriptionIcon('heroicon-m-cog-6-tooth')
                ->color('warning')
                ->chart([1, 3, 2, 4, 3, 5, 4])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ]),

            Stat::make('📊 CON HES/MIGO', number_format($stats->with_hes))
                ->description('Con HES registrado')
                ->descriptionIcon('heroicon-m-chart-bar-square')
                ->color('gray')
                ->chart([1, 2, 1, 3, 2, 4, 3])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ]),

            Stat::make('📧 FACTURADOS', number_format($stats->invoiced))
                ->description('Facturas enviadas')
                ->descriptionIcon('heroicon-m-envelope-open')
                ->color('info')
                ->chart([1, 1, 2, 1, 2, 2, 3])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ]),

            Stat::make('💳 PAGADOS', number_format($stats->paid))
                ->description('Pagos completados')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('success')
                ->chart([0, 1, 1, 1, 2, 2, 2])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ]),

            Stat::make('⏱️ PENDIENTES', number_format($pendingCount))
                ->description('En proceso/sin ejecutar')
                ->descriptionIcon('heroicon-m-clock')
                ->color('danger')
                ->chart([5, 4, 6, 3, 7, 2, 8])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ]),
        ];
    }

    protected function getColumns(): int
    {
        return 4; // 4 columnas para mejor distribución en pantalla
    }
}