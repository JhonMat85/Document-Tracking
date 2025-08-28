<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\QuotePdfController;

Route::get('/', function () {
    return view('welcome');
});

// NUEVO: Rutas del controlador PDF moderno
Route::get('/quotes/{quote}/download-pdf-new', [QuotePdfController::class, 'download'])
    ->name('quotes.download.pdf.new');

Route::get('/quotes/test-pdf-new', [QuotePdfController::class, 'test'])
    ->name('quotes.test.pdf.new');

// EXISTENTE: Rutas del controlador PDF anterior (para compatibilidad)
Route::get('/quotes/{quote}/download-pdf', [QuoteController::class, 'downloadPdf'])
    ->name('quotes.download.pdf');

Route::get('/quotes/test-pdf', [QuoteController::class, 'testPdf'])
    ->name('quotes.test.pdf');
