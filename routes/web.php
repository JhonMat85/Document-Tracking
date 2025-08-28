<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\QuoteController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/quotes/{quote}/download-pdf', [QuoteController::class, 'downloadPdf'])
    ->name('quotes.download.pdf');

Route::get('/quotes/test-pdf', [QuoteController::class, 'testPdf'])
    ->name('quotes.test.pdf');
