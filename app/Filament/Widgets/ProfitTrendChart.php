<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ReadsReportFilters;
use App\Support\ProfitLoss;
use Filament\Widgets\ChartWidget;

class ProfitTrendChart extends ChartWidget
{
    use ReadsReportFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): ?string
    {
        return 'Tren Laba per Hari';
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $daily = ProfitLoss::daily($this->reportFilters());

        return [
            'datasets' => [
                ['label' => 'Laba kotor', 'data' => array_column($daily, 'gross')],
                ['label' => 'Laba bersih', 'data' => array_column($daily, 'net')],
            ],
            'labels' => array_column($daily, 'label'),
        ];
    }
}