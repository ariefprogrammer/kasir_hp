<?php

namespace App\Filament\Widgets;

class Last14DaysChart extends DailySalesChart
{
    public function getHeading(): ?string
    {
        return 'Penjualan 14 Hari Terakhir';
    }

    protected function reportFilters(): array
    {
        return [
            'preset' => 'custom',
            'startDate' => now()->subDays(13)->toDateString(),
            'endDate' => now()->toDateString(),
        ];
    }
}