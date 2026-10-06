<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use Auditable;

    protected $fillable = ['code', 'name', 'is_cash', 'is_active', 'sort_order'];

    private static ?array $labels = null;

    protected function casts(): array
    {
        return [
            'is_cash' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => self::$labels = null);
        static::deleted(fn () => self::$labels = null);
    }

    public function auditLabel(): string
    {
        return $this->name . ' (' . $this->code . ')';
    }

    /**
     * Metode yang boleh dipilih di POS: kode => nama, urut sesuai sort_order.
     */
    public static function activeOptions(): array
    {
        return static::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'code')
            ->all();
    }

    /**
     * Semua metode (termasuk nonaktif): untuk laporan, filter, dan struk lama.
     */
    public static function labels(): array
    {
        return self::$labels ??= static::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'code')
            ->all();
    }

    public static function label(string $code): string
    {
        return self::labels()[$code] ?? ucfirst($code);
    }
}