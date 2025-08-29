<?php

namespace App\Filament\Widgets;

use App\Models\Quote;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DocumentStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        // Obtener estadísticas optimizadas en una consulta
        $totalQuotes = Quote::count();
        $totalValue = Quote::sum('total');
        $pendingCount = Quote::whereHas('state', function ($query) {
            $query->where('name', 'like', '%pendiente%');
        })->count();
        
        $inReviewCount = Quote::whereHas('state', function ($query) {
            $query->where('name', 'like', '%revisión%');
        })->count();
        
        $approvedCount = Quote::whereHas('state', function ($query) {
            $query->where('name', 'like', '%aprobad%');
        })->count();
        
        $rejectedCount = Quote::whereHas('state', function ($query) {
            $query->where('name', 'like', '%rechazad%');
        })->count();
        
        $withPOCount = Quote::has('purchaseOrders')->count();
        $executedCount = Quote::whereHas('purchaseOrders.executedServices')->count();
        $invoicedCount = Quote::whereHas('purchaseOrders.executedServices.hes.invoices')->count();
        $paidCount = Quote::whereHas('purchaseOrders.executedServices.hes.invoices.payments')->count();

        return [
            Stat::make('📋 TOTAL COTIZACIONES', number_format($totalQuotes))
                ->description('Cotizaciones en el sistema')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary')
                ->chart([1, 2, 3, 2, 4, 3, 5])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ]),

            Stat::make('💰 VALOR TOTAL', 'S/ ' . number_format($totalValue, 2))
                ->description('Valor total de cotizaciones')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->chart([10, 15, 20, 25, 22, 28, 30])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ]),

            Stat::make('⏳ PENDIENTES', number_format($pendingCount))
                ->description('Cotizaciones pendientes')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->chart([2, 3, 2, 4, 3, 2, 1])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ]),

            Stat::make('⚙️ EN PROCESO', number_format($inReviewCount + $withPOCount + $executedCount))
                ->description('En revisión, con OC y ejecutándose')
                ->descriptionIcon('heroicon-m-cog-6-tooth')
                ->color('info')
                ->chart([1, 2, 1, 3, 2, 4, 3])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ]),

            Stat::make('🚀 APROBADOS', number_format($approvedCount))
                ->description('Cotizaciones aprobadas')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->chart([1, 1, 2, 1, 2, 3, 2])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ]),

            Stat::make('📧 FACTURADOS', number_format($invoicedCount))
                ->description('Servicios facturados')
                ->descriptionIcon('heroicon-m-envelope-open')
                ->color('info')
                ->chart([0, 1, 1, 2, 1, 2, 3])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ]),

            Stat::make('💳 PAGADOS', number_format($paidCount))
                ->description('Pagos completados')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('gray')
                ->chart([0, 0, 1, 1, 1, 2, 2])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ]),

            Stat::make('🔴 RECHAZADOS', number_format($rejectedCount))
                ->description('Cotizaciones rechazadas')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger')
                ->chart([1, 0, 1, 0, 1, 0, 1])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ]),
        ];
    }

    protected function getColumns(): int
    {
        return 4; // 4 columnas para mejor distribución de los 8 stats
    }
}