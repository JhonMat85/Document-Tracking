<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Quote;

echo "=== VERIFICACIÓN DE COTIZACIONES ===\n\n";

$count = Quote::count();
echo "Total de cotizaciones: $count\n\n";

if ($count > 0) {
    $latest = Quote::latest()->first();
    echo "Última cotización:\n";
    echo "  ID: {$latest->id}\n";
    echo "  Número: {$latest->quote_number}\n";
    echo "  Creada: {$latest->created_at}\n\n";

    // Verificar números duplicados
    $duplicates = Quote::select('quote_number')
        ->groupBy('quote_number')
        ->havingRaw('COUNT(*) > 1')
        ->pluck('quote_number');

    if ($duplicates->isNotEmpty()) {
        echo "⚠️ NÚMEROS DUPLICADOS ENCONTRADOS:\n";
        foreach ($duplicates as $dup) {
            echo "  - $dup\n";
        }
    } else {
        echo "✅ No hay números duplicados\n";
    }

    echo "\nPróximo número a generar: " . Quote::generateQuoteNumber() . "\n";
} else {
    echo "No hay cotizaciones en la base de datos\n";
    echo "Primer número sería: " . Quote::generateQuoteNumber() . "\n";
}

echo "\n=== FIN DE VERIFICACIÓN ===\n";
