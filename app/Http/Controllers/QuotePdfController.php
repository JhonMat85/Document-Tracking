<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use Illuminate\Http\Request;
use TCPDF;
use Barryvdh\DomPDF\Facade\Pdf;

class QuotePdfController extends Controller
{
    /**
     * Método de prueba para verificar el nuevo controlador
     */
    public function test()
    {
        $quoteCount = Quote::count();
        $latestQuote = Quote::latest()->first();

        return response()->json([
            'status' => '✅ NUEVO CONTROLADOR PDF ACTIVO',
            'message' => 'El nuevo controlador de PDF moderno está funcionando correctamente',
            'system_info' => [
                'total_quotes' => $quoteCount,
                'latest_quote_id' => $latestQuote ? $latestQuote->id : null,
                'latest_quote_number' => $latestQuote ? $latestQuote->quote_number : null,
                'tcpdf_available' => class_exists('TCPDF'),
                'dompdf_available' => class_exists('Barryvdh\DomPDF\Facade\Pdf'),
                'new_pdf_route' => route('quotes.download.pdf.new', $latestQuote ? $latestQuote->id : 1),
                'old_pdf_route' => route('quotes.download.pdf', $latestQuote ? $latestQuote->id : 1),
            ],
            'features' => [
                'modern_design' => true,
                'responsive_layout' => true,
                'gradient_header' => true,
                'professional_styling' => true,
                'complete_data_inclusion' => true,
                'tcpdf_fallback' => true,
                'error_handling' => true,
            ],
            'timestamp' => now(),
        ]);
    }

    /**
     * Generar PDF con diseño moderno y profesional
     */
    public function download(Quote $quote)
    {
        try {
            // Cargar todas las relaciones necesarias
            $quote->load([
                'request.client',
                'rfq',
                'destinationContact',
                'details',
                'state',
                'creator',
                'updater',
                'parentQuote',
                'purchaseOrders.attachments',
                'transport',
                'attachments'
            ]);

            // Generar PDF con TCPDF (mejor calidad)
            $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

            // Configuración básica
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetMargins(15, 15, 15);
            $pdf->SetAutoPageBreak(true, 20);
            $pdf->AddPage();

            // Configurar fuentes
            $pdf->SetFont('helvetica', '', 10);

            // Generar HTML con diseño moderno
            $html = $this->generateModernHtml($quote);
            $pdf->writeHTML($html, true, false, true, false, '');

            // Nombre del archivo
            $fileName = 'COT-' . $quote->quote_number . '.pdf';

            // Retornar PDF
            return response($pdf->Output($fileName, 'S'))
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');

        } catch (\Exception $e) {
            \Log::error('Error generando PDF: ' . $e->getMessage());

            // Intentar con DomPDF como respaldo
            try {
                $html = $this->generateModernHtml($quote);
                $pdf = Pdf::loadHTML($html);
                $fileName = 'COT-' . $quote->quote_number . '.pdf';
                return $pdf->download($fileName);
            } catch (\Exception $fallbackError) {
                return response()->json([
                    'error' => 'Error generando PDF',
                    'message' => 'No se pudo generar el PDF: ' . $e->getMessage()
                ], 500);
            }
        }
    }

    /**
     * Generar HTML moderno y profesional para el PDF
     */
    private function generateModernHtml(Quote $quote): string
    {
        $client = $quote->request->client ?? null;
        $contact = $quote->destinationContact ?? null;

        return '
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>Cotización ' . $quote->quote_number . '</title>
            <style>
                ' . $this->getPdfStyles() . '
            </style>
        </head>
        <body>
            ' . $this->generateHeader($quote) . '
            ' . $this->generateClientInfo($quote, $client, $contact) . '
            ' . $this->generateServiceDetails($quote) . '
            ' . $this->generateItemsTable($quote) . '
            ' . $this->generateTotalsSection($quote) . '
            ' . $this->generateTransportInfo($quote) . '
            ' . $this->generateTermsAndConditions($quote) . '
            ' . $this->generateFooter($quote) . '
        </body>
        </html>';
    }

    /**
     * Estilos CSS modernos para el PDF
     */
    private function getPdfStyles(): string
    {
        return '
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: "Helvetica", sans-serif; font-size: 10px; line-height: 1.4; color: #333; }

            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; margin-bottom: 20px; }
            .header h1 { font-size: 24px; font-weight: bold; margin-bottom: 5px; }
            .header .quote-info { font-size: 14px; opacity: 0.9; }

            .section { margin-bottom: 20px; }
            .section-title { font-size: 14px; font-weight: bold; color: #667eea; border-bottom: 2px solid #667eea; padding-bottom: 5px; margin-bottom: 10px; }

            .info-grid { display: table; width: 100%; margin-bottom: 15px; }
            .info-row { display: table-row; }
            .info-label { display: table-cell; width: 120px; font-weight: bold; padding: 5px 10px 5px 0; background-color: #f8f9fa; }
            .info-value { display: table-cell; padding: 5px 0; }

            .items-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
            .items-table th { background: #667eea; color: white; padding: 8px; text-align: left; font-weight: bold; }
            .items-table td { padding: 8px; border-bottom: 1px solid #ddd; }
            .items-table .item-description { font-weight: bold; }
            .items-table .item-notes { font-size: 9px; color: #666; font-style: italic; }

            .totals-section { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .totals-row { display: table; width: 100%; margin-bottom: 5px; }
            .totals-label { display: table-cell; width: 200px; font-weight: bold; }
            .totals-value { display: table-cell; text-align: right; font-weight: bold; }
            .grand-total { font-size: 16px; color: #667eea; }

            .badge { display: inline-block; padding: 3px 8px; border-radius: 12px; font-size: 9px; font-weight: bold; }
            .badge-success { background: #28a745; color: white; }
            .badge-warning { background: #ffc107; color: #212529; }
            .badge-danger { background: #dc3545; color: white; }

            .transport-info { background: #e9ecef; padding: 10px; border-radius: 5px; margin: 15px 0; }
            .transport-info h4 { color: #495057; margin-bottom: 8px; }

            .terms-section { background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #ffc107; }
            .terms-section h4 { color: #856404; margin-bottom: 10px; }

            .footer { margin-top: 30px; padding-top: 20px; border-top: 2px solid #dee2e6; text-align: center; font-size: 9px; color: #6c757d; }
            .footer .company-info { margin-bottom: 10px; }
            .footer .generated-info { font-size: 8px; }

            @page { margin: 15mm; }
        ';
    }

    /**
     * Generar encabezado del PDF
     */
    private function generateHeader(Quote $quote): string
    {
        $statusColor = match($quote->state->name ?? 'draft') {
            'approved' => 'success',
            'sent', 'responded' => 'warning',
            'rejected', 'cancelled' => 'danger',
            default => 'warning'
        };

        return '
        <div class="header">
            <h1>COTIZACIÓN</h1>
            <div class="quote-info">
                <strong>N° ' . $quote->quote_number . '</strong>
                <span class="badge badge-' . $statusColor . '">' . strtoupper($quote->state->name ?? 'BORRADOR') . '</span>
            </div>
            <div style="margin-top: 10px; font-size: 12px;">
                Fecha de emisión: ' . ($quote->generation_date ? $quote->generation_date->format('d/m/Y') : date('d/m/Y')) . '<br>
                Válida hasta: ' . ($quote->expiration_date ? $quote->expiration_date->format('d/m/Y') : 'No especificada') . '
            </div>
        </div>';
    }

    /**
     * Generar información del cliente
     */
    private function generateClientInfo(Quote $quote, $client, $contact): string
    {
        $html = '<div class="section"><div class="section-title">📋 INFORMACIÓN DEL CLIENTE</div>';

        if ($client) {
            $html .= '
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Cliente:</div>
                    <div class="info-value"><strong>' . $client->name . '</strong></div>
                </div>
                <div class="info-row">
                    <div class="info-label">RUC:</div>
                    <div class="info-value">' . ($client->ruc ?? 'No especificado') . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Dirección:</div>
                    <div class="info-value">' . ($client->address ?? 'No especificada') . '</div>
                </div>
            </div>';
        }

        if ($contact) {
            $html .= '
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Contacto:</div>
                    <div class="info-value"><strong>' . $contact->name . '</strong></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Email:</div>
                    <div class="info-value">' . $contact->email . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Teléfono:</div>
                    <div class="info-value">' . ($contact->phone ?? 'No especificado') . '</div>
                </div>
            </div>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Generar detalles del servicio
     */
    private function generateServiceDetails(Quote $quote): string
    {
        $html = '<div class="section"><div class="section-title">📝 DETALLES DEL SERVICIO</div>';

        $html .= '
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Descripción:</div>
                <div class="info-value">' . ($quote->service_description ?? 'No especificada') . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Dirección Recojo:</div>
                <div class="info-value">' . ($quote->pickup_address ?? 'No especificada') . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Dirección Entrega:</div>
                <div class="info-value">' . ($quote->delivery_address ?? 'No especificada') . '</div>
            </div>';

        if ($quote->service_start_date || $quote->service_end_date) {
            $html .= '
            <div class="info-row">
                <div class="info-label">Fechas:</div>
                <div class="info-value">
                    Inicio: ' . ($quote->service_start_date ? $quote->service_start_date->format('d/m/Y') : 'No especificada') . '<br>
                    Fin: ' . ($quote->service_end_date ? $quote->service_end_date->format('d/m/Y') : 'No especificada') . '
                </div>
            </div>';
        }

        $html .= '</div></div>';
        return $html;
    }

    /**
     * Generar tabla de items
     */
    private function generateItemsTable(Quote $quote): string
    {
        $html = '<div class="section"><div class="section-title">🛍️ ITEMS DE LA COTIZACIÓN</div>';

        if ($quote->details && $quote->details->count() > 0) {
            $html .= '
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 40%;">Descripción</th>
                        <th style="width: 10%;">Cant.</th>
                        <th style="width: 15%;">Unidad</th>
                        <th style="width: 15%;">Precio Unit.</th>
                        <th style="width: 15%;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>';

            foreach ($quote->details as $index => $detail) {
                $subtotal = ($detail->quantity ?? 0) * ($detail->unit_price ?? 0);
                $html .= '
                    <tr>
                        <td>' . ($index + 1) . '</td>
                        <td>
                            <div class="item-description">' . ($detail->item_description ?? 'Sin descripción') . '</div>
                            ' . ($detail->item_notes ? '<div class="item-notes">Nota: ' . $detail->item_notes . '</div>' : '') . '
                        </td>
                        <td>' . number_format($detail->quantity ?? 0, 2) . '</td>
                        <td>' . ($detail->unit_of_measure ?? 'N/A') . '</td>
                        <td>' . number_format($detail->unit_price ?? 0, 2) . '</td>
                        <td>' . number_format($subtotal, 2) . '</td>
                    </tr>';
            }

            $html .= '</tbody></table>';
        } else {
            $html .= '<p>No hay items especificados en esta cotización.</p>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Generar sección de totales
     */
    private function generateTotalsSection(Quote $quote): string
    {
        $html = '<div class="totals-section">';

        $html .= '
            <div class="totals-row">
                <div class="totals-label">Subtotal:</div>
                <div class="totals-value">' . number_format($quote->subtotal ?? 0, 2) . ' ' . ($quote->currency ?? 'PEN') . '</div>
            </div>';

        if (($quote->tax_percentage ?? 0) > 0) {
            $html .= '
            <div class="totals-row">
                <div class="totals-label">IGV (' . ($quote->tax_percentage ?? 0) . '%):</div>
                <div class="totals-value">' . number_format($quote->tax_amount ?? 0, 2) . ' ' . ($quote->currency ?? 'PEN') . '</div>
            </div>';
        }

        $html .= '
            <div class="totals-row grand-total">
                <div class="totals-label">TOTAL:</div>
                <div class="totals-value">' . number_format($quote->total ?? 0, 2) . ' ' . ($quote->currency ?? 'PEN') . '</div>
            </div>';

        $html .= '</div>';
        return $html;
    }

    /**
     * Generar información de transporte
     */
    private function generateTransportInfo(Quote $quote): string
    {
        if (!$quote->transport) {
            return '';
        }

        $transport = $quote->transport;
        $html = '<div class="transport-info">
            <h4>🚛 INFORMACIÓN DE TRANSPORTE</h4>
            <div class="info-grid">';

        if ($transport->vehicle_type ?? null) {
            $html .= '
                <div class="info-row">
                    <div class="info-label">Tipo de Vehículo:</div>
                    <div class="info-value">' . $transport->vehicle_type . '</div>
                </div>';
        }

        if ($transport->capacity ?? null) {
            $html .= '
                <div class="info-row">
                    <div class="info-label">Capacidad:</div>
                    <div class="info-value">' . $transport->capacity . '</div>
                </div>';
        }

        if ($transport->estimated_distance ?? null) {
            $html .= '
                <div class="info-row">
                    <div class="info-label">Distancia Estimada:</div>
                    <div class="info-value">' . $transport->estimated_distance . ' km</div>
                </div>';
        }

        $html .= '</div></div>';
        return $html;
    }

    /**
     * Generar términos y condiciones
     */
    private function generateTermsAndConditions(Quote $quote): string
    {
        $notes = $quote->notes ?? 'Sin observaciones adicionales.';

        $html = '<div class="terms-section">
            <h4>📋 TÉRMINOS Y CONDICIONES</h4>
            <div style="margin-bottom: 15px;">
                <strong>Condiciones de Pago:</strong> ' . ($quote->proposed_payment_method ?? 'No especificado') . '<br>
                <strong>Validez de la oferta:</strong> ' . ($quote->expiration_date ? $quote->expiration_date->format('d/m/Y') : 'No especificada') . '<br>
                <strong>Moneda:</strong> ' . ($quote->currency ?? 'PEN') . '
            </div>
            <div>
                <strong>Observaciones:</strong><br>
                ' . nl2br($notes) . '
            </div>
        </div>';

        return $html;
    }

    /**
     * Generar pie de página
     */
    private function generateFooter(Quote $quote): string
    {
        $creator = $quote->creator;
        $updater = $quote->updater;

        $html = '<div class="footer">
            <div class="company-info">
                <strong>Document Tracking System</strong><br>
                Sistema de Gestión Documental
            </div>
            <div class="generated-info">
                Generado el: ' . date('d/m/Y H:i:s') . '<br>
                Creado por: ' . ($creator ? $creator->name : 'Sistema') . '<br>
                Última modificación: ' . ($updater ? $updater->name : 'N/A') . ' - ' . ($quote->updated_at ? $quote->updated_at->format('d/m/Y H:i') : 'N/A') . '
            </div>
        </div>';

        return $html;
    }
}
