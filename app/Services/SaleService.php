<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use App\Models\AuditLog;
use App\Models\PaymentMethod;
use App\Models\ProductUnit;

class SaleService
{
    public function __construct(private StockService $stock)
    {
    }

    /**
     * @param array<int, array{product_id: int, qty: int, deal_price: int}> $lines
     * @param array{payment_method: string, paid_amount: int, customer_name: ?string, customer_phone: ?string, note?: ?string} $payment
     */
    public function checkout(User $cashier, array $lines, array $payment): Sale
    {
        if (empty($lines)) {
            throw new InvalidArgumentException('Keranjang masih kosong.');
        }

        // Nomor invoice bisa bentrok bila dua kasir checkout bersamaan; ulangi maksimal 3 kali.
        for ($attempt = 1; ; $attempt++) {
            try {
                return DB::transaction(fn () => $this->process($cashier, $lines, $payment));
            } catch (UniqueConstraintViolationException $e) {
                if ($attempt >= 3) {
                    throw $e;
                }
            }
        }
    }

    public function void(Sale $sale, User $user, string $reason): Sale
    {
        if (! $user->is_active || ! $user->isManager()) {
            throw new InvalidArgumentException('Hanya Owner atau Admin yang dapat membatalkan transaksi.');
        }

        $reason = trim($reason);

        if (mb_strlen($reason) < 5) {
            throw new InvalidArgumentException('Alasan void minimal 5 karakter.');
        }

        return DB::transaction(function () use ($sale, $user, $reason) {
            $locked = Sale::query()
                ->whereKey($sale->getKey())
                ->lockForUpdate()
                ->with('items')
                ->firstOrFail();

            if ($locked->status === 'void') {
                throw new InvalidArgumentException('Transaksi ini sudah di-void.');
            }

            foreach ($locked->items->sortBy('product_id') as $item) {
                $this->stock->move(
                    product: Product::withTrashed()->findOrFail($item->product_id),
                    qty: $item->qty,
                    type: 'void',
                    reference: $locked->invoice_no,
                    note: 'Void: ' . $reason,
                    userId: $user->id,
                );
                if ($item->unit_id) {
                    ProductUnit::query()->whereKey($item->unit_id)->update(['status' => 'tersedia']);
                }
            }

            $locked->update([
                'status' => 'void',
                'void_reason' => $reason,
                'voided_by' => $user->id,
                'voided_at' => now(),
            ]);

            AuditLog::record(
                'void',
                $locked,
                ['status' => 'selesai'],
                ['status' => 'void', 'void_reason' => $reason],
                $locked->invoice_no . ' (' . rupiah($locked->total) . ')',
                $user->id,
            );

            return $locked;
        });
    }

    private function process(User $cashier, array $lines, array $payment): Sale
    {
        $productIds = collect($lines)->pluck('product_id')->unique()->sort()->values()->all();
        $unitIds = collect($lines)->pluck('unit_id')->filter()->unique()->sort()->values()->all();

        // Kunci baris produk (urut id agar tidak deadlock) selama transaksi berjalan.
        $products = Product::query()
            ->whereIn('id', $productIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $units = empty($unitIds)
            ? collect()
            : ProductUnit::query()
                ->whereIn('id', $unitIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

        $availableUnits = ProductUnit::query()
            ->whereIn('product_id', $productIds)
            ->where('status', 'tersedia')
            ->selectRaw('product_id, COUNT(*) as total')
            ->groupBy('product_id')
            ->pluck('total', 'product_id');

        $qtyByProduct = [];

        foreach ($lines as $line) {
            $qtyByProduct[$line['product_id']] = ($qtyByProduct[$line['product_id']] ?? 0) + (int) $line['qty'];
        }

        $subtotal = 0;
        $total = 0;
        $items = [];
        $usedUnits = [];

        foreach ($lines as $line) {
            $product = $products->get($line['product_id']);
            $qty = (int) $line['qty'];
            $deal = (int) $line['deal_price'];
            $unit = null;

            if (! $product || ! $product->is_active) {
                throw new InvalidArgumentException('Ada produk yang sudah tidak tersedia.');
            }

            if ($qty < 1) {
                throw new InvalidArgumentException("Qty {$product->name} minimal 1.");
            }

            if (($qtyByProduct[$product->id] ?? 0) > $product->stock) {
                throw new InvalidArgumentException(
                    "Stok {$product->name} tidak mencukupi (tersisa {$product->stock})."
                );
            }

            if (! empty($line['unit_id'])) {
                $unit = $units->get($line['unit_id']);

                if (! $unit || (int) $unit->product_id !== (int) $product->id) {
                    throw new InvalidArgumentException('Unit IMEI tidak valid.');
                }

                if ($unit->status !== 'tersedia' || isset($usedUnits[$unit->id])) {
                    throw new InvalidArgumentException(
                        'IMEI ' . ($unit->imei ?? $unit->imei2) . ' sudah tidak tersedia.'
                    );
                }

                if ($qty !== 1) {
                    throw new InvalidArgumentException('Satu unit ber-IMEI hanya bisa dijual satu per baris.');
                }

                $usedUnits[$unit->id] = true;
            } else {
                // "Tanpa IMEI" hanya boleh memakai stok yang belum tercatat IMEI-nya.
                $untracked = max(0, $product->stock - (int) ($availableUnits[$product->id] ?? 0));

                if ($qty > $untracked) {
                    throw new InvalidArgumentException(
                        "Sisa stok {$product->name} tanpa IMEI hanya {$untracked}. Pilih unit ber-IMEI."
                    );
                }
            }

            if ($deal <= 0) {
                throw new InvalidArgumentException("Harga deal {$product->name} harus lebih dari 0.");
            }

            if ($deal < $product->minimumDealPrice()) {
                throw new InvalidArgumentException("Harga deal {$product->name} di bawah batas minimum.");
            }

            $subtotal += $product->sell_price * $qty;
            $total += $deal * $qty;

            $items[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'imei' => $unit?->imei,
                'imei2' => $unit?->imei2,
                'unit_id' => $unit?->id,
                'qty' => $qty,
                'cost_price' => $product->cost_price,
                'standard_price' => $product->sell_price,
                'deal_price' => $deal,
                'subtotal' => $deal * $qty,
            ];
        }

        $method = PaymentMethod::query()
            ->where('code', $payment['payment_method'])
            ->where('is_active', true)
            ->first();

        if (! $method) {
            throw new InvalidArgumentException('Metode pembayaran tidak tersedia.');
        }

        $paid = $method->is_cash ? (int) $payment['paid_amount'] : $total;

        if ($paid < $total) {
            throw new InvalidArgumentException('Uang diterima kurang dari total.');
        }

        $sale = Sale::create([
            'invoice_no' => $this->nextInvoiceNo(),
            'cashier_id' => $cashier->id, // selalu dari user login, bukan dari input
            'customer_name' => $payment['customer_name'] ?: null,
            'customer_phone' => $payment['customer_phone'] ?: null,
            'note' => ($payment['note'] ?? null) ?: null,
            'subtotal' => $subtotal,
            'total' => $total,
            'payment_method' => $method->code,
            'paid_amount' => $paid,
            'change_amount' => $paid - $total,
            'status' => 'selesai',
            'sold_at' => now(),
        ]);

        foreach ($items as $item) {
            $sale->items()->create($item);

            // Unit ditandai terjual lebih dulu, baru stok dikurangi (aturan penjaga di StockService).
            if ($item['unit_id']) {
                $units->get($item['unit_id'])->update(['status' => 'terjual']);
            }

            $this->stock->move(
                product: $products->get($item['product_id']),
                qty: -$item['qty'],
                type: 'sale',
                reference: $sale->invoice_no,
                userId: $cashier->id,
            );
        }

        return $sale;
    }

    private function nextInvoiceNo(): string
    {
        $prefix = 'INV-' . now()->format('Ymd') . '-';

        $last = Sale::query()
            ->where('invoice_no', 'like', $prefix . '%')
            ->orderByDesc('invoice_no')
            ->value('invoice_no');

        $next = $last ? ((int) Str::afterLast($last, '-')) + 1 : 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}