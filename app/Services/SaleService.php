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

class SaleService
{
    public function __construct(private StockService $stock)
    {
    }

    /**
     * @param array<int, array{product_id: int, qty: int, deal_price: int}> $lines
     * @param array{payment_method: string, paid_amount: int, customer_name: ?string, customer_phone: ?string} $payment
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
        $ids = collect($lines)->pluck('product_id')->unique()->sort()->values();

        // Kunci baris produk (urut id agar tidak deadlock) selama transaksi berjalan.
        $products = Product::query()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $subtotal = 0;
        $total = 0;
        $items = [];

        foreach ($lines as $line) {
            $product = $products->get($line['product_id']);
            $qty = (int) $line['qty'];
            $deal = (int) $line['deal_price'];

            if (! $product || ! $product->is_active) {
                throw new InvalidArgumentException('Ada produk yang sudah tidak tersedia.');
            }

            if ($qty < 1) {
                throw new InvalidArgumentException("Qty {$product->name} minimal 1.");
            }

            if ($product->stock < $qty) {
                throw new InvalidArgumentException(
                    "Stok {$product->name} tidak mencukupi (tersisa {$product->stock})."
                );
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
                'qty' => $qty,
                'cost_price' => $product->cost_price,
                'standard_price' => $product->sell_price,
                'deal_price' => $deal,
                'subtotal' => $deal * $qty,
            ];
        }

        $method = $payment['payment_method'];
        $paid = $method === 'tunai' ? (int) $payment['paid_amount'] : $total;

        if ($paid < $total) {
            throw new InvalidArgumentException('Uang diterima kurang dari total.');
        }

        $sale = Sale::create([
            'invoice_no' => $this->nextInvoiceNo(),
            'cashier_id' => $cashier->id, // selalu dari user login, bukan dari input
            'customer_name' => $payment['customer_name'] ?: null,
            'customer_phone' => $payment['customer_phone'] ?: null,
            'subtotal' => $subtotal,
            'total' => $total,
            'payment_method' => $method,
            'paid_amount' => $paid,
            'change_amount' => $paid - $total,
            'status' => 'selesai',
            'sold_at' => now(),
        ]);

        foreach ($items as $item) {
            $sale->items()->create($item);

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