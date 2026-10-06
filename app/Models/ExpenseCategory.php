<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\Auditable;

class ExpenseCategory extends Model
{
    use Auditable;
    
    protected $fillable = ['name', 'is_operational'];

    protected function casts(): array
    {
        return [
            'is_operational' => 'boolean',
        ];
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }
}
