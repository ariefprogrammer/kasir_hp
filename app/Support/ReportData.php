<?php

namespace App\Support;

use App\Models\Product;
use App\Models\SaleItem;

class ReportData
{
    public static function sales(array $filters): array
    {
        $items = ReportQuery::saleItems($filters)
            ->with('sale.cashier:id,name')
            ->orderBy('id')
            ->get();

        $rows = $items->map(fn (SaleItem $item) => [
            $item->sale->invoice_no,
            $item->sale->sold_at->format('d/m/Y H:i'),
            $item->sale->cashier?->name,
            $item->product_name,
            collect([$item->imei, $item->imei2])->filter()->implode(' / '),
            $item->qty,
            $item->standard_price,
            $item->deal_price,
            ($item->standard_price - $item->deal_price) * $item->qty,
            $item->subtotal,
            $item->sale->paymentLabel(),
            $item->sale->note,
        ])->all();

        return [
            'title' => 'Laporan Penjualan',
            'period' => self::period($filters),
            'headings' => ['No. Invoice', 'Waktu', 'Kasir', 'Produk', 'IMEI', 'Qty', 'Harga standar', 'Harga deal', 'Selisih nego', 'Subtotal', 'Metode', 'Catatan'],
            'rows' => $rows,
            'footer' => [
                '', '', '', 'TOTAL', '',
                (int) $items->sum('qty'),
                '', '',
                array_sum(array_column($rows, 8)),
                (int) $items->sum('subtotal'),
                '',
                '',
            ],
            'money' => [6, 7, 8, 9],
            'orientation' => 'landscape',
        ];
    }

    public static function cashiers(array $filters): array
    {
        $users = ReportQuery::cashierTotals($filters)->orderByDesc('revenue')->get();

        $rows = $users->map(fn ($user) => [
            $user->name,
            (int) $user->trx_count,
            (int) $user->items_qty,
            (int) $user->revenue,
            $user->trx_count > 0 ? intdiv((int) $user->revenue, (int) $user->trx_count) : 0,
            (int) $user->list_total - (int) $user->revenue,
        ])->all();

        return [
            'title' => 'Laporan Omset per Kasir',
            'period' => self::period($filters),
            'headings' => ['Kasir', 'Transaksi', 'Item terjual', 'Total omset', 'Rata-rata / transaksi', 'Selisih nego'],
            'rows' => $rows,
            'footer' => [
                'TOTAL',
                array_sum(array_column($rows, 1)),
                array_sum(array_column($rows, 2)),
                array_sum(array_column($rows, 3)),
                '',
                array_sum(array_column($rows, 5)),
            ],
            'money' => [3, 4, 5],
            'orientation' => 'portrait',
        ];
    }

    public static function profitLoss(array $filters): array
    {
        $r = ProfitLoss::calculate($filters);

        $rows = [];
        $bold = [];

        $add = function (string $label, $value = '', bool $isBold = false) use (&$rows, &$bold) {
            if ($isBold) {
                $bold[] = count($rows);
            }

            $rows[] = [$label, $value];
        };

        $percent = fn ($value) => number_format($value, 1, ',', '.') . '%';

        $add('PENDAPATAN', '', true);
        $add('Penjualan (harga deal x qty)', $r['revenue']);
        $add('HPP (modal barang terjual)', $r['cogs']);
        $add('LABA KOTOR', $r['gross'], true);
        $add('Margin kotor', $percent($r['gross_margin']));
        $add('');
        $add('PENGELUARAN OPERASIONAL', '', true);

        foreach ($r['expenses'] as $expense) {
            $add($expense['name'], $expense['total']);
        }

        $add('Total pengeluaran operasional', $r['opex'], true);
        $add('');
        $add('LABA BERSIH', $r['net'], true);
        $add('Margin bersih', $percent($r['net_margin']));

        if ($r['non_operational'] > 0) {
            $add('');
            $add('Catatan: pembelian stok (tidak dihitung dalam laba rugi)', $r['non_operational']);
        }

        return [
            'title' => 'Laporan Laba Rugi',
            'period' => self::period($filters),
            'headings' => ['Keterangan', 'Jumlah'],
            'rows' => $rows,
            'bold' => $bold,
            'money' => [1],
            'orientation' => 'portrait',
        ];
    }

    public static function stock(): array
    {
        $products = Product::query()
            ->where('is_active', true)
            ->with('category:id,name')
            ->orderBy('name')
            ->get();

        $rows = $products->map(fn (Product $product) => [
            $product->sku,
            $product->name,
            $product->category?->name,
            $product->stock,
            $product->min_stock,
            $product->cost_price,
            $product->stock * $product->cost_price,
            $product->sell_price,
            match (true) {
                $product->stock <= 0 => 'Habis',
                $product->stock <= $product->min_stock => 'Menipis',
                default => 'Aman',
            },
        ])->all();

        return [
            'title' => 'Laporan Stok',
            'period' => 'Per ' . now()->format('d/m/Y H:i'),
            'headings' => ['SKU', 'Produk', 'Kategori', 'Stok', 'Stok min', 'Harga modal', 'Nilai persediaan', 'Harga jual', 'Status'],
            'rows' => $rows,
            'footer' => ['', '', 'TOTAL', array_sum(array_column($rows, 3)), '', '', array_sum(array_column($rows, 6)), '', ''],
            'money' => [5, 6, 7],
            'orientation' => 'landscape',
        ];
    }

    private static function period(array $filters): string
    {
        [$from, $until] = DateRange::resolve($filters);

        return 'Periode: ' . $from->format('d/m/Y') . ' - ' . $until->format('d/m/Y');
    }
}