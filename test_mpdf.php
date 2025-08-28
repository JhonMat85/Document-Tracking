<?php

require_once __DIR__ . '/vendor/autoload.php';

use Mpdf\Mpdf;

try {
    echo "✅ Probando MPDF básico...\n";

    $mpdf = new Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'margin_left' => 10,
        'margin_right' => 10,
        'margin_top' => 15,
        'margin_bottom' => 15,
    ]);

    $mpdf->SetAutoPageBreak(false);

    $html = '<h1>Test MPDF</h1><p>Si ves esto, MPDF funciona correctamente.</p>';

    $mpdf->WriteHTML($html);

    $output = $mpdf->Output('', 'S');

    echo "✅ MPDF funciona correctamente!\n";
    echo "Tamaño del PDF generado: " . strlen($output) . " bytes\n";

} catch (Exception $e) {
    echo "❌ Error con MPDF: " . $e->getMessage() . "\n";
}
