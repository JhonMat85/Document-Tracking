<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use Illuminate\Http\Request;
use Mpdf\Mpdf;

class QuoteController extends Controller
{
    /**
     * Descargar la cotización en formato PDF
     */
    public function downloadPdf(Quote $quote)
    {
        // Cargar las relaciones necesarias
        $quote->load(['request.client', 'details', 'transport']);

        // Crear una instancia de mPDF
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 15,
            'margin_bottom' => 15,
        ]);

        // Construir el contenido HTML del PDF
        $html = $this->buildQuoteHtml($quote);

        // Escribir el HTML en el PDF
        $mpdf->WriteHTML($html);

        // Generar el nombre del archivo
        $filename = 'Cotizacion_' . $quote->quote_number . '.pdf';

        // Devolver el PDF como una descarga
        return response()->streamDownload(
            function () use ($mpdf) {
                echo $mpdf->Output('', 'S');
            },
            $filename,
            [
                'Content-Type' => 'application/pdf',
            ]
        );
    }

    /**
     * Construye el contenido HTML para el PDF de la cotización
     *
     * @param Quote $quote
     * @return string
     */
    private function buildQuoteHtml(Quote $quote): string
    {
        $client = $quote->request->client ?? null;
        $details = $quote->details ?? collect();
        // $transport = $quote->transport ?? null; // No se usa en el formato solicitado

        // Calcular el número de filas de detalles que caben en una página aproximadamente
        // Asumiendo que el encabezado y el pie ocupan alrededor de 300px, y cada fila de detalle 30px.
        // Altura útil A4 ~ 700px. 700 - 300 = 400px / 30px = ~13 filas.
        // Si hay más, se ajustará el tamaño de la fuente o se truncará (para mantener 1 página, KISS).
        $maxDetailsToShow = 12;
        $detailsToShow = $details->take($maxDetailsToShow);
        $hasMoreDetails = $details->count() > $maxDetailsToShow;

        // Si hay más detalles, se podría agregar una nota "Continúa en el reverso" o similar.
        // Para mantenerlo simple (YAGNI) y una página, solo se muestran los primeros N.

        $html = '
        <html>
        <head>
            <style>
                @page {
                    margin: 5mm 5mm 5mm 5mm; /* Reducir márgenes para maximizar espacio */
                }
                body {
                    font-family: Arial, sans-serif;
                    font-size: 8pt; /* Tamaño de fuente pequeño para caber todo */
                    line-height: 1.2;
                    color: #000;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    table-layout: fixed;
                }
                th, td {
                    border: 0.5pt solid #000;
                    padding: 2pt;
                    vertical-align: top;
                }
                .text-center { text-align: center; }
                .text-right { text-align: right; }
                .text-left { text-align: left; }
                .bold { font-weight: bold; }
                .header-table td {
                    border: none;
                    padding: 1pt;
                }
                .header-table .logo-cell {
                    width: 25%; /* Ajustar según el tamaño del logo */
                }
                .header-table .title-cell {
                    width: 50%;
                    text-align: center;
                    font-size: 10pt;
                }
                .header-table .info-cell {
                    width: 25%;
                    font-size: 7pt;
                    text-align: right;
                }
                .section-title {
                    background-color: #d9d9d9;
                    font-weight: bold;
                    text-align: center;
                }
                .details-table th {
                    background-color: #d9d9d9;
                    font-size: 7pt;
                    padding: 1pt;
                }
                .details-table td {
                    font-size: 7pt;
                    padding: 1pt;
                }
                .totals-table {
                    width: 50%;
                    margin-left: auto;
                    font-size: 8pt;
                }
                .totals-table td {
                    border: 0.5pt solid #000;
                    padding: 2pt;
                }
                .signature-section {
                    margin-top: 15pt;
                    font-size: 8pt;
                }
                .signature-line {
                    width: 40%;
                    display: inline-block;
                    text-align: center;
                    border-top: 0.5pt solid #000;
                    margin: 30pt 5% 5pt 5%;
                }
                .footer {
                    position: fixed;
                    bottom: 10pt;
                    left: 0pt;
                    right: 0pt;
                    height: 30pt;
                    font-size: 6pt;
                    text-align: center;
                    border-top: 0.5pt solid #000;
                    padding-top: 2pt;
                }
                .small-text {
                    font-size: 6pt;
                }
            </style>
        </head>
        <body>
            <!-- Header -->
            <table class="header-table">
                <tr>
                    <td class="logo-cell">
                        <!-- Logo de la empresa -->
                        <!-- <img src="' . public_path("images/logo.png") . '" height="40"> -->
                        <!-- Placeholder para el logo -->
                        <div style="height: 40pt; border: 1pt dashed #ccc; text-align: center; line-height: 40pt; font-size: 6pt;">[Logo Empresa]</div>
                    </td>
                    <td class="title-cell bold">
                        TRANSPORTE XPRESS S.A.C.<br>
                        <span class="small-text">
                        TRANSPORTE DE CARGA, ALMACENAMIENTO, LOGISTICA Y PROYECTOS.<br>
                        Direccion: Calle Los Alamos Nro. 180 Urb. Santa Isabel - Ate - Lima.<br>
                        Telefonos: 937403921 / 972161490<br>
                        Email: operaciones@transportexpsac.com<br>
                        </span>
                    </td>
                    <td class="info-cell">
                        RUC: 20612345678<br>
                        COTIZACION N° ' . htmlspecialchars($quote->quote_number) . '
                    </td>
                </tr>
            </table>

            <!-- Client Info -->
            <table>
                <tr>
                    <td colspan="4" class="section-title">CLIENTE</td>
                </tr>
                <tr>
                    <td style="width: 15%;"><b>Razon Social:</b></td>
                    <td style="width: 35%;">' . htmlspecialchars($client->business_name ?? 'N/A') . '</td>
                    <td style="width: 15%;"><b>RUC:</b></td>
                    <td style="width: 35%;">' . htmlspecialchars($client->tax_id ?? 'N/A') . '</td>
                </tr>
                <tr>
                    <td><b>Contacto:</b></td>
                    <td>' . htmlspecialchars($quote->request->contact->full_name ?? 'N/A') . '</td>
                    <td><b>Teléfono:</b></td>
                    <td>' . htmlspecialchars($client->main_phone ?? 'N/A') . '</td>
                </tr>
                <tr>
                    <td><b>Correo:</b></td>
                    <td>' . htmlspecialchars($client->main_email ?? 'N/A') . '</td>
                    <td><b>Dirección:</b></td>
                    <td>' . htmlspecialchars($client->fiscal_address ?? 'N/A') . '</td>
                </tr>
            </table>

            <!-- Service Description -->
            <table>
                <tr>
                    <td class="section-title">SERVICIO</td>
                </tr>
                <tr>
                    <td>' . nl2br(htmlspecialchars($quote->service_description)) . '</td>
                </tr>
            </table>

            <!-- Details Table -->
            <table class="details-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">Item</th>
                        <th style="width: 10%;">Cantidad</th>
                        <th style="width: 10%;">Unidad</th>
                        <th style="width: 45%;">Descripción</th>
                        <th style="width: 15%;">Precio Unitario</th>
                        <th style="width: 15%;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
        ';

        foreach ($detailsToShow as $detail) {
            $html .= '
                    <tr>
                        <td class="text-center">' . htmlspecialchars($detail->item_order) . '</td>
                        <td class="text-right">' . number_format($detail->quantity, 2) . '</td>
                        <td class="text-center">' . htmlspecialchars($detail->unit_of_measure) . '</td>
                        <td>' . htmlspecialchars($detail->item_description) . '</td>
                        <td class="text-right">' . number_format($detail->unit_price, 2) . '</td>
                        <td class="text-right">' . number_format($detail->item_subtotal, 2) . '</td>
                    </tr>
            ';
        }

        if ($hasMoreDetails) {
            $html .= '
                    <tr>
                        <td colspan="6" class="text-center small-text">(Continúa en el reverso)</td>
                    </tr>
            ';
        }

        $html .= '
                </tbody>
            </table>
        ';

        // Totals
        $html .= '
            <table class="totals-table">
                <tr>
                    <td style="width: 70%; text-align: right;"><b>Subtotal:</b></td>
                    <td style="width: 30%; text-align: right;">' . number_format($quote->subtotal, 2) . ' ' . htmlspecialchars($quote->currency) . '</td>
                </tr>
                <tr>
                    <td style="text-align: right;"><b>IGV (18%):</b></td>
                    <td style="text-align: right;">' . number_format($quote->tax_amount, 2) . ' ' . htmlspecialchars($quote->currency) . '</td>
                </tr>
                <tr>
                    <td style="text-align: right; font-size: 10pt;"><b>TOTAL:</b></td>
                    <td style="text-align: right; font-size: 10pt;"><b>' . number_format($quote->total, 2) . ' ' . htmlspecialchars($quote->currency) . '</b></td>
                </tr>
            </table>
        ';

        // Bank Info and Signatures
        $html .= '
            <div class="signature-section">
                <table>
                    <tr>
                        <td style="width: 50%;">
                            <b>Nro. de Cuenta:</b><br>
                            BCP Soles: 191-234567890-0-12<br>
                            CCI: 00219112345678901234<br>
                            <!-- Interbank: ... -->
                        </td>
                        <td style="width: 50%;">
                            <div class="signature-line">CLIENTE</div>
                            <div class="signature-line">TRANSPORTE XPRESS S.A.C.</div>
                        </td>
                    </tr>
                </table>
            </div>
        ';

        // Footer
        $html .= '
            <div class="footer">
                TRANSPORTE XPRESS S.A.C. - Transporte de Carga, Almacenamiento, Logistica y Proyectos.<br>
                Calle Los Alamos Nro. 180 Urb. Santa Isabel - Ate - Lima. Telefonos: 937403921 / 972161490<br>
                Email: operaciones@transportexpsac.com
            </div>
        </body>
        </html>
        ';

        return $html;
    }

    /**
     * Método de prueba para verificar la funcionalidad
     */
    public function testPdf()
    {
        return response()->json([
            'message' => 'QuoteController is working',
            'status' => 'PDF functionality is now implemented'
        ]);
    }
}