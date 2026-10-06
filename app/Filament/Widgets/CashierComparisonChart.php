<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ReadsReportFilters;
use App\Support\ReportQuery;
use Filament\Widgets\ChartWidget;

class CashierComparisonChart extends ChartWidget
{
    use ReadsReportFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): ?string
    {
        return 'Perbandingan Omset Antar Kasir';
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $rows = ReportQuery::cashierTotals($this->reportFilters())
            ->orderByDesc('revenue')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Omset (Rp)',
                    'data' => $rows->map(fn ($row) => (int) $row->revenue)->all(),
                ],
            ],
            'labels' => $rows->pluck('name')->all(),
        ];
    }
}