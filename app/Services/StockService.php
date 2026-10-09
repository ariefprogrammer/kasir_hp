<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use App\Models\AuditLog;

class StockService
{
    public function move(
        Product $product,
        int $qty,
        string $type,
        ?string $reference = null,
        ?string $note = null,
        ?int $userId = null,
    ): StockMovement {
        return DB::transaction(function () use ($product, $qty, $type, $reference, $note, $userId) {
            $locked = Product::withTrashed()
                ->whereKey($product->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $oldStock = $locked->stock;
            $newStock = $oldStock + $qty;

            if ($newStock < 0) {
                throw new InvalidArgumentException('Stok tidak boleh kurang dari 0.');
            }

            $availableUnits = $locked->units()->where('status', 'tersedia')->count();

            if ($newStock < $availableUnits) {
                throw new InvalidArgumentException(
                    "Stok tidak boleh kurang dari jumlah unit ber-IMEI yang tersedia ({$availableUnits}). Hapus unit IMEI terkait lebih dulu."
                );
            }

            $locked->update(['stock' => $newStock]);

            $movement = $locked->stockMovements()->create([
                'type' => $type,
                'qty' => $qty,
                'reference' => $reference,
                'note' => $note,
                'user_id' => $userId ?? auth()->id(),
            ]);

            // Hanya penyesuaian manual yang masuk audit log (penjualan dan void punya jejak sendiri).
            if (in_array($type, ['in', 'adjustment'], true)) {
                AuditLog::record(
                    'stock_adjustment',
                    $locked,
                    ['stock' => $oldStock],
                    ['stock' => $newStock],
                    $locked->name . ($note ? ': ' . $note : ''),
                    $userId ?? auth()->id(),
                );
            }

            return $movement;
        });
    }
}