<?php

namespace App\Http\Controllers;

use App\Models\ReceiptSetting;
use App\Models\Sale;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    public function __invoke(Request $request, Sale $sale)
    {
        $user = $request->user();

        abort_unless(
            $user->is_active
                && ($user->hasAnyRole(['owner', 'admin']) || $sale->cashier_id === $user->id),
            403
        );

        $sale->load('items', 'cashier:id,name');

        $setting = ReceiptSetting::current();

        // ?size= hanya untuk sekali cetak, tidak disimpan.
        $override = $request->query('size');
        $type = in_array($override, ['58', '80', 'a5'], true) ? $override : $setting->paper_type;

        $paper = $setting->paperSpec($type);

        // Cetak otomatis dimatikan saat pengguna sedang berganti ukuran manual.
        $autoPrint = $setting->auto_print && $override === null;

        return view('receipt', compact('sale', 'setting', 'type', 'paper', 'autoPrint'));
    }
}