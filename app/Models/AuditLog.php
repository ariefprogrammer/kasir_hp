<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    public const EVENT_LABELS = [
        'login' => 'Login',
        'logout' => 'Logout',
        'login_failed' => 'Login gagal',
        'created' => 'Dibuat',
        'updated' => 'Diubah',
        'deleted' => 'Dihapus',
        'restored' => 'Dipulihkan',
        'void' => 'Void transaksi',
        'stock_adjustment' => 'Penyesuaian stok',
        'role_changed' => 'Perubahan role',
    ];

    public const EVENT_COLORS = [
        'login' => 'success',
        'logout' => 'gray',
        'login_failed' => 'danger',
        'created' => 'info',
        'updated' => 'warning',
        'deleted' => 'danger',
        'restored' => 'success',
        'void' => 'danger',
        'stock_adjustment' => 'warning',
        'role_changed' => 'warning',
    ];

    public const MODEL_LABELS = [
        Product::class => 'Produk',
        Expense::class => 'Pengeluaran',
        ExpenseCategory::class => 'Kategori Pengeluaran',
        User::class => 'Pengguna',
        Sale::class => 'Transaksi',
        PaymentMethod::class => 'Metode Pembayaran',
        ProductUnit::class => 'Unit IMEI',
    ];

    public const FIELD_LABELS = [
        'name' => 'Nama',
        'email' => 'Email',
        'password' => 'Password',
        'is_active' => 'Aktif',
        'is_operational' => 'Operasional',
        'roles' => 'Role',
        'category_id' => 'Kategori',
        'sku' => 'SKU',
        'barcode' => 'Barcode',
        'brand' => 'Merek',
        'cost_price' => 'Harga modal',
        'sell_price' => 'Harga jual',
        'stock' => 'Stok',
        'min_stock' => 'Stok minimum',
        'condition' => 'Kondisi',
        'photo' => 'Foto',
        'date' => 'Tanggal',
        'amount' => 'Nominal',
        'description' => 'Keterangan',
        'attachment' => 'Bukti',
        'expense_category_id' => 'Kategori pengeluaran',
        'status' => 'Status',
        'void_reason' => 'Alasan void',
        'code' => 'Kode',
        'is_cash' => 'Tunai (ada kembalian)',
        'sort_order' => 'Urutan',
        'imei' => 'IMEI 1 / Serial',
        'imei2' => 'IMEI 2',
        'track_imei' => 'Catat IMEI',
    ];

    protected $fillable = [
        'user_id', 'event', 'auditable_type', 'auditable_id',
        'old_values', 'new_values', 'description', 'ip_address', 'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Log bersifat append-only: tidak boleh diubah atau dihapus lewat aplikasi.
        static::updating(fn () => false);
        static::deleting(fn () => false);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(
        string $event,
        ?Model $subject = null,
        ?array $old = null,
        ?array $new = null,
        ?string $description = null,
        ?int $userId = null,
    ): self {
        $request = request();

        return static::create([
            'user_id' => $userId ?? auth()->id(),
            'event' => $event,
            'auditable_type' => $subject?->getMorphClass(),
            'auditable_id' => $subject?->getKey(),
            'old_values' => $old,
            'new_values' => $new,
            'description' => $description,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent() ? substr($request->userAgent(), 0, 255) : null,
        ]);
    }

    public function eventLabel(): string
    {
        return self::EVENT_LABELS[$this->event] ?? $this->event;
    }

    public function subjectLabel(): string
    {
        return $this->auditable_type
            ? (self::MODEL_LABELS[$this->auditable_type] ?? class_basename($this->auditable_type))
            : '-';
    }

    /**
     * Ringkasan nama kolom yang berubah, untuk kolom tabel.
     */
    public function summary(): string
    {
        $keys = array_unique(array_merge(
            array_keys($this->old_values ?? []),
            array_keys($this->new_values ?? [])
        ));

        if (empty($keys)) {
            return '-';
        }

        return implode(', ', array_map(fn ($key) => self::FIELD_LABELS[$key] ?? $key, $keys));
    }

    /**
     * Baris "Kolom: nilai lama → nilai baru" untuk halaman detail.
     *
     * @return array<int, string>
     */
    public function changeLines(): array
    {
        $old = $this->old_values ?? [];
        $new = $this->new_values ?? [];
        $keys = array_unique(array_merge(array_keys($old), array_keys($new)));

        return array_values(array_map(
            fn ($key) => (self::FIELD_LABELS[$key] ?? $key)
                . ': ' . $this->formatValue($key, $old[$key] ?? null)
                . ' → ' . $this->formatValue($key, $new[$key] ?? null),
            $keys
        ));
    }

    private function formatValue(string $key, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        if (in_array($key, ['is_active', 'is_operational'], true)) {
            return $value ? 'Ya' : 'Tidak';
        }

        if (is_array($value)) {
            return implode(', ', array_map(fn ($item) => is_scalar($item) ? (string) $item : json_encode($item), $value));
        }

        if (in_array($key, ['cost_price', 'sell_price', 'amount', 'total'], true) && is_numeric($value)) {
            return rupiah($value);
        }

        return (string) $value;
    }
}