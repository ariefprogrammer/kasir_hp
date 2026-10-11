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