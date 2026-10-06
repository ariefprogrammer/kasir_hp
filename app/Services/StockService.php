<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

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

            $newStock = $locked->stock + $qty;

            if ($newStock < 0) {
                throw new InvalidArgumentException('Stok tidak boleh kurang dari 0.');
            }

            $locked->update(['stock' => $newStock]);

            return $locked->stockMovements()->create([
                'type' => $type,
                'qty' => $qty,
                'reference' => $reference,
                'note' => $note,
                'user_id' => $userId ?? auth()->id(),
            ]);
        });
    }
}