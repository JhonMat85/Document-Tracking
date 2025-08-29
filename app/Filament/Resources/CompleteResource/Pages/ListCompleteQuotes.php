<?php

namespace App\Filament\Resources\CompleteResource\Pages;

use App\Filament\Resources\CompleteResource;
use App\Filament\Widgets\CompleteStatsWidget;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use Filament\Actions\ExportAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class ListCompleteQuotes extends ListRecords
{
    protected static string $resource = CompleteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export_excel')
                ->label('📥 Exportar a Excel')
                ->color('success')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function () {
                    $quotes = static::getResource()::getEloquentQuery()
                        ->with([
                            'request:id,requesting_department',
                            'rfq:id,rfq_number',
                            'destinationContact:id,full_name',
                            'purchaseOrders:id,quote_id,po_number',
                            'purchaseOrders.executedServices:id,purchase_order_id',
                            'purchaseOrders.executedServices.hes:id,executed_service_id,hes_number',
                            'purchaseOrders.executedServices.hes.invoices:id,hes_id,invoice_number',
                            'purchaseOrders.executedServices.hes.invoices.payments:id,invoice_id,net_received_amount',
                            'state:id,name'
                        ])
                        ->get();

                    $filename = 'dashboard-completo-' . now()->format('Y-m-d-H-i-s') . '.xlsx';
                    
                    return response()->streamDownload(function () use ($quotes) {
                        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                        $sheet = $spreadsheet->getActiveSheet();
                        
                        // 🎨 CONFIGURACIÓN CORPORATIVA
                        $sheet->setTitle('Dashboard Completo');
                        
                        // 📊 TÍTULO PRINCIPAL
                        $sheet->setCellValue('A1', 'DASHBOARD COMPLETO DE COTIZACIONES');
                        $sheet->mergeCells('A1:O1');
                        
                        // Estilo del título principal
                        $titleStyle = [
                            'font' => [
                                'bold' => true,
                                'size' => 16,
                                'color' => ['rgb' => 'FFFFFF']
                            ],
                            'alignment' => [
                                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                            ],
                            'fill' => [
                                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'startColor' => ['rgb' => '1F2937'] // Gris corporativo
                            ]
                        ];
                        $sheet->getStyle('A1:O1')->applyFromArray($titleStyle);
                        $sheet->getRowDimension('1')->setRowHeight(35);
                        
                        // 📅 INFORMACIÓN DE GENERACIÓN
                        $sheet->setCellValue('A2', 'Generado: ' . now()->format('d/m/Y H:i:s') . ' | Total: ' . $quotes->count() . ' cotizaciones');
                        $sheet->mergeCells('A2:O2');
                        $infoStyle = [
                            'font' => ['size' => 10, 'italic' => true, 'color' => ['rgb' => '6B7280']],
                            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                            'fill' => [
                                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'F9FAFB']
                            ]
                        ];
                        $sheet->getStyle('A2:O2')->applyFromArray($infoStyle);
                        
                        // 📋 ENCABEZADOS DE COLUMNAS
                        $headers = [
                            'A3' => '# Cotización', 'B3' => 'Costo', 'C3' => 'Fecha Envío', 'D3' => 'Área',
                            'E3' => 'Contacto', 'F3' => 'Descripción', 'G3' => 'RFQ/ERP', 'H3' => 'OC',
                            'I3' => 'Ejecutado', 'J3' => 'HES/MIGO', 'K3' => 'Facturado', 'L3' => 'Num Factura',
                            'M3' => 'Pagado', 'N3' => 'Observaciones', 'O3' => 'Estado'
                        ];
                        
                        foreach ($headers as $cell => $value) {
                            $sheet->setCellValue($cell, $value);
                        }
                        
                        // Estilo de encabezados
                        $headerStyle = [
                            'font' => [
                                'bold' => true,
                                'size' => 11,
                                'color' => ['rgb' => 'FFFFFF']
                            ],
                            'alignment' => [
                                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                            ],
                            'fill' => [
                                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'startColor' => ['rgb' => '374151'] // Gris oscuro corporativo
                            ],
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                    'color' => ['rgb' => 'D1D5DB']
                                ]
                            ]
                        ];
                        $sheet->getStyle('A3:O3')->applyFromArray($headerStyle);
                        $sheet->getRowDimension('3')->setRowHeight(25);
                        
                        // 📊 DATOS CON FORMATO ALTERNADO
                        $row = 4;
                        foreach ($quotes as $index => $quote) {
                            // Datos
                            $sheet->setCellValue('A' . $row, $quote->quote_number);
                            $sheet->setCellValue('B' . $row, ($quote->currency === 'USD' ? 'USD ' : 'S/ ') . number_format($quote->total, 2));
                            $sheet->setCellValue('C' . $row, $quote->sent_date ? $quote->sent_date->format('d/m/Y') : 'Sin enviar');
                            $sheet->setCellValue('D' . $row, $quote->request?->requesting_department ?? 'Sin área');
                            $sheet->setCellValue('E' . $row, $quote->destinationContact?->full_name ?? 'Sin contacto');
                            $sheet->setCellValue('F' . $row, $quote->service_description);
                            $sheet->setCellValue('G' . $row, $quote->rfq?->rfq_number ?? '--');
                            
                            // Estados con colores
                            $hasOC = $quote->purchaseOrders->isNotEmpty();
                            $isExecuted = $quote->purchaseOrders()->whereHas('executedServices')->exists();
                            $hasHES = $quote->purchaseOrders()->whereHas('executedServices.hes')->exists();
                            $isInvoiced = $quote->purchaseOrders()->whereHas('executedServices.hes.invoices')->exists();
                            $isPaid = $quote->purchaseOrders()->whereHas('executedServices.hes.invoices.payments')->exists();
                            
                            $sheet->setCellValue('H' . $row, $hasOC ? '✓ Sí' : '✗ No');
                            $sheet->setCellValue('I' . $row, $isExecuted ? '✓ Sí' : '✗ No');
                            $sheet->setCellValue('J' . $row, $hasHES ? '✓ Sí' : '✗ No');
                            $sheet->setCellValue('K' . $row, $isInvoiced ? '✓ Sí' : '✗ No');
                            $sheet->setCellValue('L' . $row, $quote->purchaseOrders()->with('executedServices.hes.invoices')->get()->flatMap(fn($po) => $po->executedServices)->flatMap(fn($es) => $es->hes)->flatMap(fn($hes) => $hes->invoices)->first()?->invoice_number ?? '--');
                            $sheet->setCellValue('M' . $row, $isPaid ? '✓ Sí' : '✗ No');
                            $sheet->setCellValue('N' . $row, $quote->notes ?? '');
                            $sheet->setCellValue('O' . $row, $quote->state?->name ?? '');
                            
                            // Formato de filas alternadas
                            $rowColor = ($index % 2 === 0) ? 'FFFFFF' : 'F8FAFC';
                            $sheet->getStyle('A' . $row . ':O' . $row)->getFill()
                                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                ->getStartColor()->setRGB($rowColor);
                            
                            // Colores para estados
                            $greenStyle = ['font' => ['color' => ['rgb' => '059669']], 'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D1FAE5']]];
                            $redStyle = ['font' => ['color' => ['rgb' => 'DC2626']], 'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEE2E2']]];
                            
                            if ($hasOC) $sheet->getStyle('H' . $row)->applyFromArray($greenStyle);
                            else $sheet->getStyle('H' . $row)->applyFromArray($redStyle);
                            
                            if ($isExecuted) $sheet->getStyle('I' . $row)->applyFromArray($greenStyle);
                            else $sheet->getStyle('I' . $row)->applyFromArray($redStyle);
                            
                            if ($hasHES) $sheet->getStyle('J' . $row)->applyFromArray($greenStyle);
                            else $sheet->getStyle('J' . $row)->applyFromArray($redStyle);
                            
                            if ($isInvoiced) $sheet->getStyle('K' . $row)->applyFromArray($greenStyle);
                            else $sheet->getStyle('K' . $row)->applyFromArray($redStyle);
                            
                            if ($isPaid) $sheet->getStyle('M' . $row)->applyFromArray($greenStyle);
                            else $sheet->getStyle('M' . $row)->applyFromArray($redStyle);
                            
                            // Bordes para todas las celdas de datos
                            $sheet->getStyle('A' . $row . ':O' . $row)->getBorders()->getAllBorders()
                                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
                                ->getColor()->setRGB('E5E7EB');
                            
                            $row++;
                        }
                        
                        // 🎯 AJUSTE AUTOMÁTICO DE COLUMNAS
                        $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O'];
                        foreach ($columns as $col) {
                            $sheet->getColumnDimension($col)->setAutoSize(true);
                        }
                        
                        // Ancho máximo para columnas largas
                        $sheet->getColumnDimension('F')->setWidth(30); // Descripción
                        $sheet->getColumnDimension('N')->setWidth(25); // Observaciones
                        
                        // 🔒 FILTROS Y CONGELACIÓN
                        $sheet->setAutoFilter('A3:O' . ($row - 1));
                        $sheet->freezePane('A4');
                        
                        // 📊 PIE DE PÁGINA CON RESUMEN
                        $totalRow = $row + 1;
                        $sheet->setCellValue('A' . $totalRow, 'RESUMEN:');
                        $sheet->setCellValue('B' . $totalRow, '=SUBTOTAL(109,B4:B' . ($row - 1) . ')');
                        $sheet->setCellValue('H' . $totalRow, '=COUNTIF(H4:H' . ($row - 1) . ',"✓*")');
                        $sheet->setCellValue('I' . $totalRow, '=COUNTIF(I4:I' . ($row - 1) . ',"✓*")');
                        $sheet->setCellValue('K' . $totalRow, '=COUNTIF(K4:K' . ($row - 1) . ',"✓*")');
                        $sheet->setCellValue('M' . $totalRow, '=COUNTIF(M4:M' . ($row - 1) . ',"✓*")');
                        
                        $summaryStyle = [
                            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '374151']],
                            'fill' => [
                                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'EFF6FF']
                            ],
                            'borders' => [
                                'top' => [
                                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
                                    'color' => ['rgb' => '3B82F6']
                                ]
                            ]
                        ];
                        $sheet->getStyle('A' . $totalRow . ':O' . $totalRow)->applyFromArray($summaryStyle);
                        
                        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                        $writer->save('php://output');
                    }, $filename, [
                        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ]);
                })
                ->modalHeading('Exportar Dashboard Completo')
                ->modalDescription('Se descargará inmediatamente el archivo Excel con todos los datos.')
                ->modalSubmitActionLabel('Descargar Ahora')
                ->requiresConfirmation(),
        ];
    }

    // Widgets de estadísticas completas en el header
    protected function getHeaderWidgets(): array
    {
        return [
            CompleteStatsWidget::class,
        ];
    }

    // Personalizar el título de la página
    public function getTitle(): string
    {
        return '📊 Dashboard Completo de Cotizaciones';
    }

    // Personalizar el subtítulo
    public function getSubheading(): ?string
    {
        return 'Vista completa con todas las columnas del proceso de cotización - Exportable a Excel';
    }

    // Eager loading ultra-optimizado para máximo rendimiento con todas las relaciones
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
                'request:id,requesting_department',
                'rfq:id,rfq_number',
                'destinationContact:id,full_name',
                'purchaseOrders:id,quote_id,po_number',
                'purchaseOrders.executedServices:id,purchase_order_id',
                'purchaseOrders.executedServices.hes:id,executed_service_id,hes_number',
                'purchaseOrders.executedServices.hes.invoices:id,hes_id,invoice_number',
                'purchaseOrders.executedServices.hes.invoices.payments:id,invoice_id,net_received_amount',
                'state:id,name'
            ])
            ->orderBy('quotes.created_at', 'desc');
    }
}