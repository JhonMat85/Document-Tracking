<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use Illuminate\Http\Request;

class QuoteController extends Controller
{
    /**
     * Descargar la cotización en formato PDF
     */
    public function downloadPdf(Quote $quote)
    {
        // Cargar las relaciones necesarias
        $quote->load(['request.client', 'details']);

        // Aquí puedes agregar la lógica básica de generación de PDF
        // Por ahora retornamos una respuesta simple
        return response()->json([
            'message' => 'PDF functionality removed',
            'quote_id' => $quote->id,
            'quote_number' => $quote->quote_number
        ]);
    }

    /**
     * Método de prueba para verificar la funcionalidad
     */
    public function testPdf()
    {
        return response()->json([
            'message' => 'QuoteController is working',
            'status' => 'PDF functionality removed'
        ]);
    }
}
}