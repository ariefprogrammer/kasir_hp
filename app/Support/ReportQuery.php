<?php

namespace App\Support;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use App\Models\StockMovement;

class ReportQuery
{
    public static function sales(?array $filters): Builder
    {
        [$from, $until] = DateRange::resolve($filters);
        $user = auth()->user();

        return Sale::query()
            ->completed()
            ->soldBetween($from, $until)
            ->visibleTo($user)
            ->when(
                $user->isManager() && filled($filters['cashier_id'] ?? null),
                fn (Builder $q) => $q->where('cashier_id', $filters['cashier_id'])
            )
            ->when(
                filled($filters['payment_method'] ?? null),
                fn (Builder $q) => $q->where('payment_method', $filters['payment_method'])
            );
    }

    public static function saleItems(?array $filters): Builder
    {
        return SaleItem::query()
            ->whereIn('sale_id', self::sales($filters)->select('sales.id'))
            ->when(
                filled($filters['product_id'] ?? null),
                fn (Builder $q) => $q->where('product_id', $filters['product_id'])
            )
            ->when(
                filter_var($filters['loss_only'] ?? false, FILTER_VALIDATE_BOOLEAN),
                fn (Builder $q) => $q->whereColumn('deal_price', '<', 'cost_price')
            )
            ->when(
                filled($filters['category_id'] ?? null),
                fn (Builder $q) => $q->whereHas(
                    'product',
                    fn (Builder $p) => $p->withTrashed()->where('category_id', $filters['category_id'])
                )
            );
    }

    public static function cashierTotals(?array $filters): Builder
    {
        [$from, $until] = DateRange::resolve($filters);
        $user = auth()->user();

        $salesScope = fn ($q) => $q->completed()->soldBetween($from, $until);

        return User::query()
            ->whereHas('sales', $salesScope)
            ->withCount(['sales as trx_count' => $salesScope])
            ->withSum(['sales as revenue' => $salesScope], 'total')
            ->withSum(['sales as list_total' => $salesScope], 'subtotal')
            ->withSum(['saleItems as items_qty' => fn ($q) => $q
                ->where('sales.status', 'selesai')
                ->whereBetween('sales.sold_at', [$from, $until])], 'qty')
            ->when(! $user->isManager(), fn (Builder $q) => $q->whereKey($user->id))
            ->when(
                $user->isManager() && filled($filters['cashier_id'] ?? null),
                fn (Builder $q) => $q->whereKey($filters['cashier_id'])
            );
    }

    public static function stockMovements(?array $filters): Builder
    {
        [$from, $until] = DateRange::resolve($filters);

        return StockMovement::query()
            ->whereBetween('stock_movements.created_at', [$from, $until])
            ->when(
                ! empty($filters['movement_types'] ?? []),
                fn (Builder $q) => $q->whereIn('stock_movements.type', (array) $filters['movement_types'])
            )
            ->when(
                filled($filters['product_id'] ?? null),
                fn (Builder $q) => $q->where('stock_movements.product_id', $filters['product_id'])
            );
    }
}