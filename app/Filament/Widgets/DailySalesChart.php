<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ReadsReportFilters;
use App\Support\DateRange;
use App\Support\ReportQuery;
use Carbon\CarbonPeriod;
use Filament\Widgets\ChartWidget;

class DailySalesChart extends ChartWidget
{
    use ReadsReportFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): ?string
    {
        return 'Penjualan per Hari';
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $filters = $this->reportFilters();
        [$from, $until] = DateRange::resolve($filters);

        $rows = ReportQuery::saleItems($filters)
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->selectRaw('DATE(sales.sold_at) as day, SUM(sale_items.subtotal) as revenue')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('revenue', 'day');

        $labels = [];
        $data = [];

        foreach (CarbonPeriod::create($from->copy()->startOfDay(), $until->copy()->startOfDay()) as $day) {
            $labels[] = $day->format('d M');
            $data[] = (int) ($rows[$day->toDateString()] ?? 0);
        }

        return [
            'datasets' => [
                ['label' => 'Penjualan (Rp)', 'data' => $data],
            ],
            'labels' => $labels,
        ];
    }
}