<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\Auditable;

class Expense extends Model
{
    use SoftDeletes, Auditable;

    protected $fillable = [
        'expense_category_id', 'user_id', 'date', 'amount', 'description', 'attachment',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditLabel(): string
    {
        return 'Pengeluaran ' . rupiah($this->amount) . ' (' . ($this->date?->format('d/m/Y') ?? '-') . ')';
    }
}
