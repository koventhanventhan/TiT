<?php

use App\Http\Controllers\PaymentReceiptController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap.xml', [SitemapController::class, 'index']);

// Public secure signed download route for payment receipts (used by WhatsApp links)
Route::get('/receipts/{payment}/download', [PaymentReceiptController::class, 'downloadSigned'])
    ->name('receipts.download')
    ->middleware('signed');

Route::get('/', function () {
    return view('welcome');
});
