<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = ['product_id', 'type', 'qty', 'reference', 'user_id', 'note'];

    public const TYPE_LABELS = [
        'in' => 'Stok masuk',
        'adjustment' => 'Koreksi stok',
        'sale' => 'Penjualan',
        'void' => 'Void transaksi',
    ];

    public const TYPE_COLORS = [
        'in' => 'success',
        'adjustment' => 'warning',
        'sale' => 'info',
        'void' => 'danger',
    ];

    public static function typeLabel(string $type): string
    {
        return self::TYPE_LABELS[$type] ?? ucfirst($type);
    }

    /**
     * Jenis yang sudah dikenal ditambah jenis lain yang pernah tercatat.
     *
     * @return array<string, string>
     */
    public static function typeOptions(): array
    {
        $recorded = static::query()->distinct()->pluck('type')->all();
        $options = [];

        foreach (array_unique(array_merge(array_keys(self::TYPE_LABELS), $recorded)) as $type) {
            $options[$type] = self::typeLabel($type);
        }

        return $options;
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
