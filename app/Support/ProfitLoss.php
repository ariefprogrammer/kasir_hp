<?php

namespace App\Support;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Carbon\CarbonPeriod;

class ProfitLoss
{
    public static function calculate(?array $filters): array
    {
        [$from, $until] = DateRange::resolve($filters);
        $range = [$from->toDateString(), $until->toDateString()];

        $sales = ReportQuery::saleItems($filters)
            ->toBase()
            ->selectRaw('COALESCE(SUM(subtotal), 0) as revenue, COALESCE(SUM(cost_price * qty), 0) as cogs')
            ->first();

        $revenue = (int) $sales->revenue;
        $cogs = (int) $sales->cogs;
        $gross = $revenue - $cogs;

        $expenses = ExpenseCategory::query()
            ->where('is_operational', true)
            ->withSum(['expenses as total' => fn ($q) => $q->whereBetween('date', $range)], 'amount')
            ->orderBy('name')
            ->get()
            ->filter(fn ($category) => (int) $category->total > 0)
            ->map(fn ($category) => ['name' => $category->name, 'total' => (int) $category->total])
            ->values()
            ->all();

        $opex = (int) array_sum(array_column($expenses, 'total'));
        $net = $gross - $opex;

        $nonOperational = (int) Expense::query()
            ->whereBetween('date', $range)
            ->whereHas('category', fn ($q) => $q->where('is_operational', false))
            ->sum('amount');

        return [
            'revenue' => $revenue,
            'cogs' => $cogs,
            'gross' => $gross,
            'gross_margin' => $revenue > 0 ? round($gross / $revenue * 100, 1) : 0,
            'expenses' => $expenses,
            'opex' => $opex,
            'net' => $net,
            'net_margin' => $revenue > 0 ? round($net / $revenue * 100, 1) : 0,
            'non_operational' => $nonOperational,
        ];
    }

    /**
     * @return array<int, array{label: string, gross: int, net: int}>
     */
    public static function daily(?array $filters): array
    {
        [$from, $until] = DateRange::resolve($filters);

        $items = ReportQuery::saleItems($filters)
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->selectRaw('DATE(sales.sold_at) as day, SUM(sale_items.subtotal) as revenue, SUM(sale_items.cost_price * sale_items.qty) as cogs')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $expenses = Expense::query()
            ->whereBetween('date', [$from->toDateString(), $until->toDateString()])
            ->whereHas('category', fn ($q) => $q->where('is_operational', true))
            ->get()
            ->groupBy(fn (Expense $expense) => $expense->date->toDateString())
            ->map(fn ($group) => (int) $group->sum('amount'));

        $result = [];

        foreach (CarbonPeriod::create($from->copy()->startOfDay(), $until->copy()->startOfDay()) as $day) {
            $key = $day->toDateString();
            $revenue = (int) ($items->get($key)?->revenue ?? 0);
            $cogs = (int) ($items->get($key)?->cogs ?? 0);
            $opex = (int) $expenses->get($key, 0);

            $result[] = [
                'label' => $day->format('d M'),
                'gross' => $revenue - $cogs,
                'net' => $revenue - $cogs - $opex,
            ];
        }

        return $result;
    }
}