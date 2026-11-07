<?php

use App\Http\Controllers\PaymentReceiptController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap.xml', [SitemapController::class, 'index']);
Route::get('/robots.txt', [SitemapController::class, 'robots']);

// Public secure signed download route for payment receipts (used by WhatsApp links)
Route::get('/receipts/{payment}/download', [PaymentReceiptController::class, 'downloadSigned'])
    ->name('receipts.download')
    ->middleware('signed');

// Legacy /super-panel alias -> permanent HTTP 301 redirect to canonical config('filament.path') (/super-admin)
Route::any('/super-panel/{any?}', function (\Illuminate\Http\Request $request, ?string $any = null) {
    $basePath = '/' . trim((string) config('filament.path', 'super-admin'), '/');
    $target = ($any !== null && $any !== '')
        ? $basePath . '/' . ltrim($any, '/')
        : $basePath;
    $query = $request->getQueryString();

    return redirect($target . ($query ? '?' . $query : ''), 301);
})->where('any', '.*')->name('super-panel.legacy-redirect');

Route::get('/', function () {
    return view('welcome');
});
