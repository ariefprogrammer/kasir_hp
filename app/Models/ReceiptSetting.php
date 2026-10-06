<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceiptSetting extends Model
{
    protected $fillable = [
        'name', 'is_active',
        'store_name', 'store_address', 'store_phone', 'footer', 'logo', 'show_logo',
        'paper_type', 'custom_width_mm', 'font_size', 'auto_print',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'show_logo' => 'boolean',
            'auto_print' => 'boolean',
            'custom_width_mm' => 'integer',
            'font_size' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Hanya satu profil yang boleh aktif.
        static::saved(function (ReceiptSetting $setting) {
            if ($setting->is_active) {
                static::query()
                    ->whereKeyNot($setting->getKey())
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
            }
        });

        // Profil aktif tidak boleh dihapus (return false membatalkan penghapusan).
        static::deleting(fn (ReceiptSetting $setting) => ! $setting->is_active);
    }

    public static function current(): self
    {
        return static::query()->where('is_active', true)->first()
            ?? new static([
                'name' => 'Bawaan',
                'store_name' => config('app.name'),
                'footer' => 'Terima kasih atas kunjungan Anda',
                'paper_type' => '58',
                'custom_width_mm' => 58,
                'show_logo' => false,
                'auto_print' => false,
            ]);
    }

    public function logoUrl(): ?string
    {
        return ($this->show_logo && $this->logo)
            ? asset('storage/' . $this->logo)
            : null;
    }

    /**
     * @return array{width: string, font: int, page: string, margin: string, pad: string, print_pad: string}
     */
    public function paperSpec(?string $type = null): array
    {
        $type ??= $this->paper_type;
        $width = min(210, max(40, (int) ($this->custom_width_mm ?: 58)));

        $spec = match ($type) {
            '80' => ['width' => '80mm', 'font' => 12, 'page' => '80mm auto', 'margin' => '0', 'pad' => '4mm', 'print_pad' => '4mm'],
            'a5' => ['width' => '148mm', 'font' => 13, 'page' => 'A5', 'margin' => '10mm', 'pad' => '10mm', 'print_pad' => '0'],
            'custom' => ['width' => $width . 'mm', 'font' => $width >= 100 ? 13 : 12, 'page' => $width . 'mm auto', 'margin' => '0', 'pad' => '3mm', 'print_pad' => '3mm'],
            default => ['width' => '58mm', 'font' => 11, 'page' => '58mm auto', 'margin' => '0', 'pad' => '3mm', 'print_pad' => '3mm'],
        };

        $spec['font'] = $this->font_size ?: $spec['font'];

        return $spec;
    }
}