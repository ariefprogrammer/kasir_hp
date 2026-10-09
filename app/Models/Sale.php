<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Sale extends Model
{
    protected $fillable = [
        'invoice_no', 'cashier_id', 'customer_name', 'customer_phone',
        'subtotal', 'total', 'payment_method', 'paid_amount', 'change_amount',
        'status', 'void_reason', 'voided_by', 'voided_at', 'sold_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'integer',
            'total' => 'integer',
            'paid_amount' => 'integer',
            'change_amount' => 'integer',
            'sold_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function getRouteKeyName(): string
    {
        return 'invoice_no';
    }

    public function paymentLabel(): string
    {
        return PaymentMethod::label($this->payment_method);
    }

    public function receiptText(): string
    {
        $setting = ReceiptSetting::current();
        $line = str_repeat('-', 24);

        $lines = [
            '*' . $setting->store_name . '*',
            'No: ' . $this->invoice_no,
            'Tanggal: ' . $this->sold_at->format('d/m/Y H:i'),
            'Kasir: ' . ($this->cashier?->name ?? '-'),
            $line,
        ];

        foreach ($this->items as $item) {
            $lines[] = $item->product_name;

            if ($item->imei) {
                $lines[] = 'IMEI: ' . $item->imei;
            }

            if ($item->imei2) {
                $lines[] = 'IMEI 2: ' . $item->imei2;
            }

            $lines[] = "{$item->qty} x " . rupiah($item->deal_price) . ' = ' . rupiah($item->subtotal);
        }

        $lines[] = $line;
        $lines[] = 'Total: ' . rupiah($this->total);
        $lines[] = 'Bayar (' . $this->paymentLabel() . '): ' . rupiah($this->paid_amount);

        if ($this->change_amount > 0) {
            $lines[] = 'Kembali: ' . rupiah($this->change_amount);
        }

        $lines[] = '';
        $lines[] = (string) $setting->footer;

        return implode("\n", $lines);
    }

    public function whatsappUrl(): string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->customer_phone);

        $number = match (true) {
            $digits === '' => '',
            str_starts_with($digits, '0') => '62' . substr($digits, 1),
            str_starts_with($digits, '62') => $digits,
            str_starts_with($digits, '8') => '62' . $digits,
            default => $digits,
        };

        return 'https://wa.me/' . $number . '?text=' . rawurlencode($this->receiptText());
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'selesai');
    }

    public function scopeSoldBetween(Builder $query, $from, $until): Builder
    {
        return $query->whereBetween('sold_at', [$from, $until]);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $user->isManager() ? $query : $query->where('cashier_id', $user->id);
    }
}
