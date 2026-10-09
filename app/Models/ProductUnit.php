<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductUnit extends Model
{
    use Auditable;
    protected array $auditExclude = ['status'];

    protected $fillable = ['product_id', 'imei', 'imei2', 'status', 'note'];

    protected function imei(): Attribute
    {
        return Attribute::make(set: fn ($value) => self::normalize($value));
    }

    protected function imei2(): Attribute
    {
        return Attribute::make(set: fn ($value) => self::normalize($value));
    }

    public function auditLabel(): string
    {
        return (string) ($this->imei ?? $this->imei2 ?? '#' . $this->getKey());
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    // Penjualan terakhir yang memakai unit ini (valid hanya bila status = terjual).
    public function saleItem(): HasOne
    {
        return $this->hasOne(SaleItem::class, 'unit_id')->latestOfMany();
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', 'tersedia');
    }

    public static function normalize(?string $value): ?string
    {
        $value = strtoupper(trim((string) $value));

        return $value === '' ? null : $value;
    }

    /**
     * Mengembalikan pesan kesalahan, atau null bila IMEI/serial valid.
     */
    public static function validateIdentifier(?string $value, ?int $ignoreId = null): ?string
    {
        $value = self::normalize($value);

        if ($value === null) {
            return null;
        }

        if (! preg_match('/^[A-Z0-9\-]{5,30}$/', $value)) {
            return 'Gunakan huruf, angka, atau tanda hubung (5 sampai 30 karakter).';
        }

        if (preg_match('/^\d{15}$/', $value) && ! self::luhnValid($value)) {
            return 'IMEI tidak valid, periksa kembali digitnya.';
        }

        $exists = static::query()
            ->when($ignoreId, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->where(fn (Builder $query) => $query->where('imei', $value)->orWhere('imei2', $value))
            ->exists();

        return $exists ? 'IMEI/serial ini sudah terdaftar.' : null;
    }

    private static function luhnValid(string $number): bool
    {
        $sum = 0;
        $double = false;

        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $digit = (int) $number[$i];

            if ($double) {
                $digit *= 2;

                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
            $double = ! $double;
        }

        return $sum % 10 === 0;
    }
}