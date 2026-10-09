<?php

namespace App\Support;

class PosCart
{
    /**
     * Mengubah format keranjang lama (kunci = id produk) ke format baru (p12 / u55).
     */
    public static function normalize(array $cart): array
    {
        $result = [];

        foreach ($cart as $key => $line) {
            if (! is_array($line)) {
                continue;
            }

            if (! isset($line['product_id'])) {
                $line = ['product_id' => (int) $key, 'unit_id' => null] + $line;
                $key = 'p' . (int) $key;
            }

            $result[$key] = $line;
        }

        return $result;
    }

    public static function qtyForProduct(array $cart, int $productId): int
    {
        return (int) collect($cart)
            ->where('product_id', $productId)
            ->sum(fn ($line) => max(1, (int) ($line['qty'] ?? 1)));
    }
}