<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use Illuminate\Http\Request;
use TCPDF;
use Barryvdh\DomPDF\Facade\Pdf;

class QuoteController extends Controller
{
    /**
     * Descargar la cotización en formato PDF
     */
    public function downloadPdf(Quote $quote)
    {
        // Cargar relaciones necesarias con manejo seguro de errores
        try {
            $relations = [
                'request.client',
                'rfq',
                'destinationContact',
                'details',
                'state'
            ];

            // Intentar cargar relaciones opcionales
            try {
                $quote->load(array_merge($relations, ['creator', 'updater', 'parentQuote', 'purchaseOrders']));
            } catch (\Exception $e) {
                // Si fallan las relaciones opcionales, cargar solo las básicas
                $quote->load($relations);
            }
        } catch (\Exception $e) {
            // Manejar cualquier error de carga de relaciones básicas
            \Log::warning('Error cargando relaciones de cotización: ' . $e->getMessage());
        }
        
        // Intentar cargar transport de forma segura
        try {
            $quote->load(['transport']);
        } catch (\Exception $e) {
            // Si la tabla no existe, continuar sin transport
            \Log::info('Tabla transport no disponible o vacía');
        }
        
                // Intentar generar PDF con TCPDF primero, si falla usar DomPDF
        try {
            // Crear PDF con TCPDF
            $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

            // Configuración optimizada para A4
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetMargins(12, 10, 12); // Márgenes optimizados
            $pdf->SetAutoPageBreak(true, 10); // Salto automático de página
            $pdf->AddPage();

            // Configurar fuente por defecto
            $pdf->SetFont('helvetica', '', 10);

            // Generar HTML del PDF
            $html = $this->generatePdfHtml($quote);
            $pdf->writeHTML($html, true, false, true, false, '');

            // Nombre del archivo
            $fileName = 'cotizacion-' . $quote->quote_number . '.pdf';

            // Salida del PDF
            return response($pdf->Output($fileName, 'S'))
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');

        } catch (\Exception $tcpdfError) {
            \Log::warning('TCPDF falló, intentando con DomPDF: ' . $tcpdfError->getMessage());

            try {
                // Intentar con DomPDF como respaldo
                $html = $this->generatePdfHtml($quote);
                $pdf = Pdf::loadHTML($html);
                $fileName = 'cotizacion-' . $quote->quote_number . '.pdf';

                return $pdf->download($fileName);

            } catch (\Exception $dompdfError) {
                \Log::error('Ambos generadores de PDF fallaron: TCPDF=' . $tcpdfError->getMessage() . ', DomPDF=' . $dompdfError->getMessage());

                // Generar respuesta de error
                return response()->json([
                    'error' => 'Error generando PDF',
                    'message' => 'No se pudo generar el PDF. Contacte al administrador.',
                    'details' => [
                        'quote_id' => $quote->id,
                        'quote_number' => $quote->quote_number,
                        'tcpdf_error' => $tcpdfError->getMessage(),
                        'dompdf_error' => $dompdfError->getMessage()
                    ]
                ], 500);
            }
        }
    }
    
    /**
     * Método de prueba para verificar la funcionalidad del PDF
     */
    public function testPdf()
    {
        // Verificar si hay cotizaciones disponibles
        $quoteCount = Quote::count();
        $latestQuote = Quote::latest()->first();

        // Verificar rutas disponibles
        $routes = [
            'download_pdf' => route('quotes.download.pdf', $latestQuote ? $latestQuote->id : 1),
            'test_pdf' => route('quotes.test.pdf'),
            'admin_quotes' => url('/admin/quotes')
        ];

        return response()->json([
            'status' => 'PDF Controller Active',
            'message' => 'El controlador de PDF está funcionando correctamente',
            'system_info' => [
                'total_quotes' => $quoteCount,
                'latest_quote_id' => $latestQuote ? $latestQuote->id : null,
                'latest_quote_number' => $latestQuote ? $latestQuote->quote_number : null,
                'tcpdf_available' => class_exists('TCPDF'),
                'dompdf_available' => class_exists('Barryvdh\DomPDF\Facade\Pdf'),
            ],
            'available_routes' => $routes,
            'timestamp' => now(),
            'server_info' => [
                'php_version' => PHP_VERSION,
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time')
            ]
        ]);
    }

    /**
     * Generar HTML para el PDF con formato profesional completo
     */
    private function generatePdfHtml(Quote $quote): string
    {
        $client = $quote->request->client ?? null;
        $contact = $quote->destinationContact ?? null;
        $transport = $quote->transport ?? null;
        $rfq = $quote->rfq ?? null;
        $creator = $quote->creator ?? null;
        $updater = $quote->updater ?? null;
        $parentQuote = $quote->parentQuote ?? null;
        
        // Fecha actual
        $currentDate = now()->format('d \d\e F \d\e Y');
        
        // Información de versión
        $versionText = match($quote->version) {
            1 => 'ORIGINAL',
            2 => 'PRIMERA REVISIÓN',
            3 => 'SEGUNDA REVISIÓN',
            4 => 'TERCERA REVISIÓN',
            5 => 'CUARTA REVISIÓN',
            default => 'VERSIÓN ' . $quote->version
        };

        // Calcular totales si no existen
        $subtotal = $quote->subtotal ?: 0;
        $taxAmount = $quote->tax_amount ?: 0;
        $total = $quote->total ?: ($subtotal + $taxAmount);

        // Verificar si la cotización está vencida
        $isExpired = $quote->expiration_date && $quote->expiration_date < now();
        $statusText = $isExpired ? 'VENCIDA' : 'VIGENTE';
        $statusColor = $isExpired ? '#e74c3c' : '#27ae60';

        // Método de pago
        $paymentMethodText = match($quote->proposed_payment_method) {
            'factoring' => 'FACTORING',
            'direct_payment' => 'PAGO DIRECTO',
            'cash' => 'CONTADO',
            'others' => 'OTROS',
            default => strtoupper($quote->proposed_payment_method ?: 'POR DEFINIR')
        };
        
        return '
        <style>
            @page { size: A4; margin: 0; }
            body { 
                font-family: "Helvetica", Arial, sans-serif; 
                font-size: 9px; 
                line-height: 1.3;
                margin: 0;
                padding: 0;
                color: #2c3e50;
            }

            /* Header Styles */
            .header { 
                text-align: center; 
                margin-bottom: 20px;
                padding-top: 10px;
                border-bottom: 2px solid #3498db;
                background: linear-gradient(135deg, #ecf0f1 0%, #bdc3c7 100%);
            }
            .company-name { 
                font-size: 20px;
                font-weight: bold; 
                margin-bottom: 5px;
                color: #2c3e50;
                letter-spacing: 1px;
            }
            .subtitle { 
                font-size: 11px;
                margin-bottom: 8px;
                color: #7f8c8d;
                font-style: italic;
            }

            /* Main Content Styles */
            .content-section {
                margin-bottom: 15px;
            }

            /* Table Styles */
            .info-table {
                width: 100%; 
                border-collapse: collapse; 
                margin-bottom: 12px;
                border: 1px solid #bdc3c7;
            }
            .info-table td, .info-table th {
                border: 1px solid #bdc3c7;
                padding: 6px 8px;
                vertical-align: top; 
                font-size: 8.5px;
            }

            /* Header Cells */
            .header-primary {
                background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
                color: white;
                font-weight: bold;
                text-align: center;
                font-size: 11px;
                padding: 8px;
            }
            .header-secondary {
                background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
                color: white;
                font-weight: bold; 
                text-align: center; 
                font-size: 9px;
                padding: 5px;
            }
            .header-accent {
                background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
                color: white;
                font-weight: bold; 
                text-align: center; 
                font-size: 10px;
                padding: 6px;
            }

            /* Data Cells */
            .data-cell {
                background-color: #ecf0f1;
                font-size: 8px;
                line-height: 1.2;
            }
            .data-cell-alt {
                background-color: #f8f9fa;
                font-size: 8px;
                line-height: 1.2;
            }

            /* Special Sections */
            .quote-number {
                background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
                color: white;
                font-weight: bold;
                text-align: center;
                font-size: 14px;
                padding: 12px;
                margin: 10px 0;
                border-radius: 5px;
                letter-spacing: 2px;
            }

            /* Items Table */
            .items-table {
                width: 100%;
                border-collapse: collapse;
                margin: 15px 0;
                border: 2px solid #34495e;
            }
            .items-table th {
                background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%);
                color: white;
                font-weight: bold; 
                text-align: center; 
                font-size: 9px;
                padding: 8px 4px;
                border: 1px solid #34495e;
            }
            .items-table td {
                border: 1px solid #bdc3c7;
                padding: 6px 4px;
                font-size: 8px;
                text-align: center;
                background-color: #f8f9fa;
            }
            .items-table .item-description {
                text-align: left;
                background-color: #ecf0f1;
            }

            /* Totals Section */
            .totals-table {
                width: 60%;
                margin: 20px auto;
                border-collapse: collapse;
                border: 2px solid #e74c3c;
            }
            .totals-table td {
                border: 1px solid #e74c3c;
                padding: 8px;
                font-size: 10px;
                text-align: center; 
            }
            .totals-label {
                background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
                color: white;
                font-weight: bold;
                width: 40%;
            }
            .totals-value {
                background-color: #ffeaea;
                font-weight: bold;
                color: #c0392b;
                font-size: 12px;
            }

            /* Footer */
            .footer-section {
                margin-top: 30px;
                padding-top: 15px;
                border-top: 1px solid #bdc3c7;
                text-align: center;
                font-size: 8px;
                color: #7f8c8d;
            }

            /* Version Badge */
            .version-badge {
                background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
                color: white;
                font-weight: bold;
                text-align: center;
                font-size: 9px;
                padding: 4px 8px;
                margin: 10px 0;
                display: inline-block;
                border-radius: 12px;
            }

            /* Status Badge */
            .status-badge {
                background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
                color: white;
                font-weight: bold;
                text-align: center;
                font-size: 8px;
                padding: 3px 6px;
                margin: 5px 0;
                display: inline-block;
                border-radius: 10px;
            }
        </style>
        
        <!-- Header Section -->
        <div class="header">
            <div class="company-name">DOCUMENT TRACKING</div>
            <div class="subtitle">Sistema Integral de Gestión Documental y Logística</div>
        </div>
        
        <!-- Quote Number & Version -->
        <div class="quote-number">
            COTIZACIÓN N° ' . $quote->quote_number . '-' . date('Y') . '
        </div>
        <div class="version-badge">' . $versionText . '</div>
        <div style="background: linear-gradient(135deg, ' . $statusColor . ' 0%, ' . ($isExpired ? '#c0392b' : '#229954') . ' 100%); color: white; font-weight: bold; text-align: center; font-size: 9px; padding: 4px 8px; margin: 5px 0; display: inline-block; border-radius: 12px;">' . $statusText . '</div>

        <!-- Client Information Table -->
        <table class="info-table">
            <tr>
                <td colspan="4" class="header-primary">INFORMACIÓN DEL CLIENTE</td>
            </tr>
            <tr>
                <td class="header-secondary" style="width: 15%;">Cliente</td>
                <td class="data-cell" style="width: 35%;">' . htmlspecialchars($client ? $client->business_name : 'No especificado') . '</td>
                <td class="header-secondary" style="width: 15%;">Contacto</td>
                <td class="data-cell-alt" style="width: 35%;">' . htmlspecialchars($contact ? $contact->name : 'No especificado') . '</td>
            </tr>
            <tr>
                <td class="header-secondary">Dirección</td>
                <td class="data-cell">' . htmlspecialchars($client ? $client->address : 'No especificada') . '</td>
                <td class="header-secondary">Cargo</td>
                <td class="data-cell-alt">' . htmlspecialchars($contact ? $contact->position : 'No especificado') . '</td>
            </tr>
            <tr>
                <td class="header-secondary">Solicitud</td>
                <td class="data-cell">' . htmlspecialchars($quote->request->request_number ?? 'No especificada') . '</td>
                <td class="header-secondary">RFQ</td>
                <td class="data-cell-alt">' . htmlspecialchars($rfq ? $rfq->rfq_number : 'No aplica') . '</td>
            </tr>
        </table>
            
        <!-- Service Description -->
        <table class="info-table">
            <tr>
                <td colspan="2" class="header-primary">DESCRIPCIÓN DEL SERVICIO</td>
            </tr>
            <tr>
                <td colspan="2" class="data-cell" style="font-size: 9px; line-height: 1.4; padding: 10px;">' . htmlspecialchars($quote->service_description) . '</td>
            </tr>
        </table>
            
        <!-- Addresses Table -->
        <table class="info-table">
            <tr>
                <td colspan="2" class="header-accent">DIRECCIONES DEL SERVICIO</td>
            </tr>
            <tr>
                <td class="header-secondary" style="width: 20%;">Dirección de Recojo</td>
                <td class="data-cell" style="width: 80%;">' . htmlspecialchars($quote->pickup_address) . '</td>
            </tr>
            <tr>
                <td class="header-secondary">Dirección de Entrega</td>
                <td class="data-cell-alt">' . htmlspecialchars($quote->delivery_address) . '</td>
            </tr>
        </table>
        
        <!-- Dates Table -->
        <table class="info-table">
            <tr>
                <td colspan="4" class="header-accent">PROGRAMACIÓN DEL SERVICIO</td>
            </tr>
            <tr>
                <td class="header-secondary" style="width: 20%;">Fecha Inicio</td>
                <td class="data-cell" style="width: 30%;">' . ($quote->service_start_date ? $quote->service_start_date->format('d/m/Y H:i') : 'Por coordinar') . '</td>
                <td class="header-secondary" style="width: 20%;">Fecha Fin</td>
                <td class="data-cell-alt" style="width: 30%;">' . ($quote->service_end_date ? $quote->service_end_date->format('d/m/Y H:i') : 'Por coordinar') . '</td>
            </tr>
            <tr>
                <td class="header-secondary">Fecha Generación</td>
                <td class="data-cell">' . $quote->generation_date->format('d/m/Y H:i') . '</td>
                <td class="header-secondary">Fecha Vencimiento</td>
                <td class="data-cell-alt">' . ($quote->expiration_date ? $quote->expiration_date->format('d/m/Y') : 'No especificada') . '</td>
            </tr>
        </table>
        
        <!-- Items Table -->
        <table class="items-table">
            <tr>
                <td colspan="6" class="header-primary">DETALLE DE ITEMS</td>
            </tr>
            <tr>
                <th style="width: 6%;">#</th>
                <th style="width: 8%;">Orden</th>
                <th style="width: 12%;">Cantidad</th>
                <th style="width: 12%;">Unidad</th>
                <th style="width: 16%;">Precio Unitario</th>
                <th style="width: 14%;">Subtotal</th>
                <th style="width: 32%;">Descripción</th>
            </tr>';

        // Generate items rows
        $itemRows = '';
        $totalItems = 0;
        $runningTotal = 0;

        if ($quote->details->isNotEmpty()) {
            foreach ($quote->details as $index => $detail) {
                $itemSubtotal = (float)$detail->quantity * (float)$detail->unit_price;
                $runningTotal += $itemSubtotal;
                $totalItems++;

                $itemRows .= '
                <tr>
                    <td>' . ($index + 1) . '</td>
                    <td>' . htmlspecialchars($detail->item_order) . '</td>
                    <td>' . number_format($detail->quantity, 2) . '</td>
                    <td>' . htmlspecialchars($detail->unit_of_measure) . '</td>
                    <td style="font-weight: bold;">' . strtoupper($quote->currency) . ' ' . number_format($detail->unit_price, 2) . '</td>
                    <td style="font-weight: bold; color: #e74c3c;">' . strtoupper($quote->currency) . ' ' . number_format($itemSubtotal, 2) . '</td>
                    <td class="item-description">
                        <strong>' . htmlspecialchars($detail->item_description ?: 'Sin descripción') . '</strong>
                        ' . ($detail->item_notes ? '<br><em style="font-size: 7px; color: #666;">Nota: ' . htmlspecialchars($detail->item_notes) . '</em>' : '') . '
                    </td>
                </tr>';
            }
        } else {
            $itemRows = '<tr><td colspan="7" style="text-align: center; font-style: italic;">No hay items detallados en esta cotización</td></tr>';
        }

        $html = $itemRows . '
        </table>

        <!-- Totals Section -->
        <table class="totals-table">
            <tr>
                <td colspan="2" class="header-accent">RESUMEN FINANCIERO</td>
            </tr>
            <tr>
                <td class="totals-label">Subtotal (' . $totalItems . ' items)</td>
                <td class="totals-value">' . strtoupper($quote->currency) . ' ' . number_format($subtotal, 2) . '</td>
            </tr>
            <tr>
                <td class="totals-label">Impuesto (' . ($quote->tax_percentage ?: 18) . '%)</td>
                <td class="totals-value">' . strtoupper($quote->currency) . ' ' . number_format($taxAmount, 2) . '</td>
            </tr>
            <tr>
                <td class="totals-label" style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); font-size: 12px;">TOTAL</td>
                <td class="totals-value" style="background: linear-gradient(135deg, #ffeaea 0%, #ffcccc 100%); font-size: 14px; color: #c0392b; font-weight: bold;">' . strtoupper($quote->currency) . ' ' . number_format($total, 2) . '</td>
            </tr>
        </table>

        <!-- Payment & Transport Info -->
        <table class="info-table">
            <tr>
                <td colspan="4" class="header-accent">INFORMACIÓN DE PAGO Y TRANSPORTE</td>
            </tr>
            <tr>
                <td class="header-secondary" style="width: 20%;">Moneda</td>
                <td class="data-cell" style="width: 30%;">' . strtoupper($quote->currency) . '</td>
                <td class="header-secondary" style="width: 20%;">Forma de Pago</td>
                <td class="data-cell-alt" style="width: 30%;">' . $paymentMethodText . '</td>
            </tr>
            <tr>
                <td class="header-secondary">Conductor</td>
                <td class="data-cell">' . htmlspecialchars($transport ? $transport->driver_name : 'Por asignar') . '</td>
                <td class="header-secondary">Licencia</td>
                <td class="data-cell-alt">' . htmlspecialchars($transport ? $transport->driver_license : 'Por confirmar') . '</td>
            </tr>
            <tr>
                <td class="header-secondary">Vehículo</td>
                <td class="data-cell">' . htmlspecialchars($transport ? $transport->vehicle_model : 'Por asignar') . '</td>
                <td class="header-secondary">Placa</td>
                <td class="data-cell-alt">' . htmlspecialchars($transport ? $transport->vehicle_plate : 'Por asignar') . '</td>
            </tr>
            <tr>
                <td class="header-secondary">Capacidad</td>
                <td class="data-cell">' . htmlspecialchars($transport ? $transport->vehicle_capacity : 'Por definir') . '</td>
                <td class="header-secondary">Seguro</td>
                <td class="data-cell-alt">' . ($transport && $transport->includes_insurance ? '✓ Incluye seguro' : '✗ No incluye seguro') . '</td>
            </tr>
        </table>
            
        <!-- Notes Section -->
        <table class="info-table">
            <tr>
                <td colspan="2" class="header-secondary">OBSERVACIONES ADICIONALES</td>
            </tr>
            <tr>
                <td colspan="2" class="data-cell" style="font-style: italic;">' . htmlspecialchars($quote->notes ?: 'Sin observaciones adicionales.') . '</td>
            </tr>
        </table>
        
        <!-- Attachments Section -->
        ' . ($quote->attachments->isNotEmpty() ? '
        <table class="info-table">
            <tr>
                <td colspan="2" class="header-secondary">DOCUMENTOS ADJUNTOS</td>
            </tr>
            ' . $quote->attachments->map(function($attachment, $index) {
                return '
                <tr>
                    <td class="data-cell" style="width: 10%;">' . ($index + 1) . '</td>
                    <td class="data-cell-alt" style="width: 90%;">' . htmlspecialchars($attachment->original_filename ?: $attachment->filename) . '</td>
                </tr>';
            })->implode('') . '
        </table>
        ' : '') . '

        <!-- Status and Dates -->
        <table class="info-table">
            <tr>
                <td colspan="4" class="header-secondary">ESTADO Y SEGUIMIENTO</td>
            </tr>
            <tr>
                <td class="header-secondary" style="width: 20%;">Estado</td>
                <td class="data-cell" style="width: 30%;">' . htmlspecialchars($quote->state->name ?? 'No definido') . '</td>
                <td class="header-secondary" style="width: 20%;">Template</td>
                <td class="data-cell-alt" style="width: 30%;">' . htmlspecialchars($quote->template_used ?: 'Por definir') . '</td>
            </tr>
            <tr>
                <td class="header-secondary">Fecha Envío</td>
                <td class="data-cell">' . ($quote->sent_date ? $quote->sent_date->format('d/m/Y') : 'No enviada') . '</td>
                <td class="header-secondary">Fecha Respuesta</td>
                <td class="data-cell-alt">' . ($quote->response_date ? $quote->response_date->format('d/m/Y') : 'Pendiente') . '</td>
            </tr>
            <tr>
                <td class="header-secondary">Creado por</td>
                <td class="data-cell">' . htmlspecialchars($creator ? $creator->name : 'Sistema') . '</td>
                <td class="header-secondary">Actualizado por</td>
                <td class="data-cell-alt">' . htmlspecialchars($updater ? $updater->name : 'Sistema') . '</td>
            </tr>
            ' . ($parentQuote ? '
            <tr>
                <td class="header-secondary">Cotización Original</td>
                <td class="data-cell" colspan="3">' . htmlspecialchars($parentQuote->quote_number) . ' (Revisión ' . $quote->version . ')</td>
            </tr>
            ' : '') . '
        </table>

        <!-- Purchase Orders Section -->
        ' . ($quote->purchaseOrders->isNotEmpty() ? '
        <table class="info-table">
            <tr>
                <td colspan="4" class="header-accent">ÓRDENES DE COMPRA RELACIONADAS</td>
            </tr>
            <tr>
                <td class="header-secondary" style="width: 15%;">#</td>
                <td class="header-secondary" style="width: 25%;">N° Orden</td>
                <td class="header-secondary" style="width: 30%;">Fecha</td>
                <td class="header-secondary" style="width: 30%;">Estado</td>
            </tr>
            ' . $quote->purchaseOrders->map(function($order, $index) {
                return '
                <tr>
                    <td class="data-cell">' . ($index + 1) . '</td>
                    <td class="data-cell">' . htmlspecialchars($order->purchase_order_number) . '</td>
                    <td class="data-cell-alt">' . $order->created_at->format('d/m/Y') . '</td>
                    <td class="data-cell">' . htmlspecialchars($order->status ?? 'Activa') . '</td>
                </tr>';
            })->implode('') . '
        </table>
        ' : '') . '

        <!-- Additional Information -->
        <table class="info-table">
            <tr>
                <td colspan="4" class="header-secondary">INFORMACIÓN DEL SISTEMA</td>
            </tr>
            <tr>
                <td class="header-secondary" style="width: 20%;">ID Cotización</td>
                <td class="data-cell" style="width: 30%;">' . $quote->id . '</td>
                <td class="header-secondary" style="width: 20%;">ID Solicitud</td>
                <td class="data-cell-alt" style="width: 30%;">' . ($quote->request ? $quote->request->id : 'N/A') . '</td>
            </tr>
            <tr>
                <td class="header-secondary">Fecha Creación</td>
                <td class="data-cell">' . $quote->created_at->format('d/m/Y H:i') . '</td>
                <td class="header-secondary">Última Actualización</td>
                <td class="data-cell-alt">' . $quote->updated_at->format('d/m/Y H:i') . '</td>
            </tr>
        </table>

        <!-- Footer -->
        <div class="footer-section">
            <div style="margin-bottom: 10px; font-weight: bold;">DOCUMENT TRACKING - Sistema Integral de Gestión Documental y Logística</div>
            <div>Generado el ' . $currentDate . ' | Cotización válida hasta: ' . ($quote->expiration_date ? $quote->expiration_date->format('d/m/Y') : 'No especificada') . '</div>
            <div style="margin-top: 5px;">ID: ' . $quote->id . ' | Estado: <span style="color: ' . $statusColor . '; font-weight: bold;">' . $statusText . '</span> | Versión: ' . $versionText . '</div>
            <div style="margin-top: 3px;">Solicitud: ' . ($quote->request ? $quote->request->request_number : 'N/A') . ' | RFQ: ' . ($rfq ? $rfq->rfq_number : 'N/A') . '</div>
            <div style="margin-top: 8px; font-style: italic; color: #95a5a6;">Esta cotización es confidencial y está destinada únicamente al destinatario nombrado. Cualquier uso no autorizado está prohibido.</div>
            <div style="margin-top: 5px; font-size: 7px; color: #bdc3c7;">Sistema generado automáticamente - Para consultas contactar al administrador del sistema</div>
        </div>

        <!-- Signature -->
        <div style="margin-top: 40px; text-align: center; font-weight: bold; font-size: 11px; color: #2c3e50;">
            Atentamente,<br>
            <span style="margin-top: 10px; display: block; font-size: 12px;">Equipo de Document Tracking</span>
        </div>
        ';
    }
}