<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSpec extends Model
{
    protected $fillable = [
        'product_id', 'ram', 'storage', 'screen_size', 'battery',
        'camera_mp', 'chipset', 'os', 'network', 'color',
    ];

    protected function casts(): array
    {
        return [
            'screen_size' => 'decimal:1',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
