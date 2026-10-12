<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Pos;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ReportExportController;
use App\Livewire\Rekomendasi;

Route::get('/', function () {
    return redirect('/admin');
});

Route::livewire('/pos', Pos::class)
    ->middleware('auth')
    ->name('pos');

Route::get('/struk/{sale}', ReceiptController::class)
    ->middleware('auth')
    ->name('receipt.show');

Route::get('/laporan-ekspor/{report}/{format}', ReportExportController::class)
    ->middleware('auth')
    ->whereIn('report', ['sales', 'cashiers', 'profit-loss', 'stock', 'stock-adjustments'])
    ->whereIn('format', ['xlsx', 'pdf'])
    ->name('reports.export');

Route::livewire('/rekomendasi', Rekomendasi::class)
    ->middleware('auth')
    ->name('recommendation');

Route::get('/manifest.webmanifest', function () {
    return response()->json([
        'name' => config('app.name'),
        'short_name' => config('app.name'),
        'lang' => 'id',
        'start_url' => '/admin',
        'scope' => '/',
        'display' => 'standalone',
        'background_color' => '#ffffff',
        'theme_color' => '#f59e0b',
        'icons' => [
            ['src' => '/icons/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => '/icons/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
        ],
    ], 200, ['Content-Type' => 'application/manifest+json']);
})->name('pwa.manifest');