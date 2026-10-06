<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id', 'name', 'sku', 'barcode', 'brand',
        'cost_price', 'sell_price', 'stock', 'min_stock',
        'condition', 'photo', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'integer',
            'sell_price' => 'integer',
            'stock' => 'integer',
            'min_stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function spec(): HasOne
    {
        return $this->hasOne(ProductSpec::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function minimumDealPrice(): int
    {
        return (int) $this->cost_price;
    }
}
