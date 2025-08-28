<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use Illuminate\Http\Request;
use TCPDF;

class QuoteController extends Controller
{
    /**
     * Descargar la cotización en formato PDF
     */
    public function downloadPdf(Quote $quote)
    {
        // Cargar relaciones necesarias - manejo seguro de transport
        $quote->load(['request.client', 'destinationContact', 'details', 'state']);
        
        // Intentar cargar transport de forma segura
        try {
            $quote->load(['transport']);
        } catch (\Exception $e) {
            // Si la tabla no existe, continuar sin transport
        }
        
        // Crear PDF con configuración automática para A4
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
    }
    
    /**
     * Generar HTML para el PDF con formato profesional
     */
    private function generatePdfHtml(Quote $quote): string
    {
        $client = $quote->request->client ?? null;
        $contact = $quote->destinationContact ?? null;
        $transport = $quote->transport ?? null;
        
        // Fecha actual
        $currentDate = now()->format('d \d\e F \d\e Y');
        
        // Obtener ítems de detalle con formato optimizado
        $detailItems = $quote->details->map(function($detail, $index) {
            return sprintf('%02d. %s (Cantidad: %d)', 
                $index + 1, 
                $detail->item_description,
                $detail->quantity
            );
        })->implode('<br>');
        
        // Si no hay detalles, usar la descripción del servicio
        if ($quote->details->isEmpty()) {
            $detailItems = '01. ' . $quote->service_description;
        }
        
        return '
        <style>
            @page { size: A4; margin: 0; }
            body { 
                font-family: "Helvetica", Arial, sans-serif; 
                font-size: 9px; 
                line-height: 1.2;
                margin: 0;
                padding: 0;
            }
            .header { 
                text-align: center; 
                margin-bottom: 15px; 
                padding-top: 5px;
            }
            .company-name { 
                font-size: 16px; 
                font-weight: bold; 
                margin-bottom: 3px; 
                color: #2c3e50;
            }
            .subtitle { 
                font-size: 10px; 
                margin-bottom: 10px; 
                color: #7f8c8d;
            }
            .date-location { 
                font-size: 9px; 
                margin-bottom: 12px; 
                text-align: left;
                line-height: 1.3;
            }
            .main-table { 
                width: 100%; 
                border-collapse: collapse; 
                margin-bottom: 8px;
                table-layout: auto;
            }
            .main-table td, .main-table th { 
                border: 0.5px solid #34495e; 
                padding: 3px 4px; 
                vertical-align: top; 
                font-size: 8px;
                word-wrap: break-word;
            }
            .header-cell { 
                background-color: #ecf0f1; 
                font-weight: bold; 
                text-align: center; 
                font-size: 9px;
            }
            .section-header { 
                background-color: #bdc3c7; 
                font-weight: bold; 
                text-align: center; 
                padding: 4px;
                font-size: 8px;
                white-space: nowrap;
            }
            .quote-header { 
                background-color: #95a5a6; 
                font-weight: bold; 
                text-align: center; 
                font-size: 9px;
                color: #2c3e50;
            }
            .cost-section { 
                background-color: #d5dbdb; 
                text-align: center; 
                font-weight: bold;
                font-size: 8px;
            }
            .detail-text {
                font-size: 8px;
                line-height: 1.1;
            }
            .signature {
                font-size: 9px;
                margin-top: 15px;
                text-align: left;
            }
        </style>
        
        <div class="header">
            <div class="company-name">DOCUMENT TRACKING</div>
            <div class="subtitle">Sistema de Seguimiento de Documentos</div>
        </div>
        
        <div class="date-location">
            ' . ($client ? $client->address : 'Lima') . ', ' . $currentDate . '<br>
            Señor:<br>
            ' . ($contact ? $contact->name : ($client ? $client->business_name : 'Estimado Cliente')) . '<br>
            ' . ($contact ? $contact->position : 'Coordinador de Logística') . '<br>
            ' . ($client ? $client->business_name : 'Empresa') . '<br>
            Presente. -
        </div>
        
        <table class="main-table">
            <tr>
                <td colspan="2" class="header-cell">DOCUMENT TRACKING</td>
                <td colspan="2" class="quote-header">
                    COTIZACION<br>
                    N° ' . $quote->quote_number . '-' . date('Y') . '
                </td>
            </tr>
            
            <tr>
                <td class="section-header" style="width: 12%;">AREA</td>
                <td style="width: 23%;">Comercial</td>
                <td class="section-header" style="width: 15%;">Contacto</td>
                <td style="width: 50%;">Document Tracking</td>
            </tr>
            
            <tr>
                <td class="section-header">SERVICIO</td>
                <td colspan="3" class="detail-text">' . htmlspecialchars($quote->service_description) . '</td>
            </tr>
            
            <tr>
                <td class="section-header">RECOJO</td>
                <td class="detail-text">' . htmlspecialchars($quote->pickup_address) . '</td>
                <td class="section-header">DESTINO</td>
                <td class="detail-text">' . htmlspecialchars($quote->delivery_address) . '</td>
            </tr>
            
            <tr>
                <td class="section-header">IDA</td>
                <td class="detail-text">' . ($quote->service_start_date ? $quote->service_start_date->format('d/m/Y H:i') . 'h' : 'Por coordinar') . '</td>
                <td class="section-header">RETORNO</td>
                <td class="detail-text">' . ($quote->service_end_date ? $quote->service_end_date->format('d/m/Y H:i') . 'h' : 'Por coordinar') . '</td>
            </tr>
            
            <tr>
                <td class="section-header">DETALLE<br>DEL<br>SERVICIO</td>
                <td colspan="3" class="detail-text">' . $detailItems . '</td>
            </tr>
        </table>
        
        <table class="main-table">
            <tr>
                <td class="section-header" colspan="2">OBSERVACIONES</td>
            </tr>
            <tr>
                <td colspan="2" class="detail-text">' . htmlspecialchars($quote->notes ?: 'Sin observaciones adicionales.') . '</td>
            </tr>
        </table>
        
        <table class="main-table">
            <tr>
                <td class="section-header" style="width: 25%;">FORMA DE PAGO</td>
                <td class="section-header" style="width: 75%;">FACTORING</td>
            </tr>
            
            <tr>
                <td rowspan="2" class="section-header">DATOS<br>TRANSPORTE</td>
                <td class="detail-text">
                    <strong>CONDUCTOR:</strong> ' . ($transport ? htmlspecialchars($transport->driver_name) : 'Por asignar') . ' | 
                    <strong>BREVETE:</strong> ' . ($transport ? htmlspecialchars($transport->driver_license) : 'Por confirmar') . '
                </td>
            </tr>
            <tr>
                <td class="detail-text">
                    <strong>PLACA:</strong> ' . ($transport ? htmlspecialchars($transport->vehicle_plate) : 'Por asignar') . ' | 
                    <strong>SEGURO:</strong> ' . ($transport && $transport->includes_insurance ? 'Incluye' : 'No incluye') . '
                </td>
            </tr>
            
            <tr>
                <td class="section-header">TIPO DE MONEDA</td>
                <td class="section-header">' . strtoupper($quote->currency) . '</td>
            </tr>
            
            <tr>
                <td class="cost-section">Costo del<br>servicio</td>
                <td class="cost-section">' . strtoupper($quote->currency) . '/ ' . number_format($quote->total, 2) . '</td>
            </tr>
            <tr>
                <td class="section-header" colspan="2">MAS IGV</td>
            </tr>
        </table>
        
        <div class="signature">Atentamente,</div>
        ';
    }
}