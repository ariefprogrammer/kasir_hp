<?php

namespace App\Filament\Widgets\Concerns;

use Filament\Widgets\Concerns\InteractsWithPageFilters;

trait ReadsReportFilters
{
    use InteractsWithPageFilters;

    protected function reportFilters(): array
    {
        if (property_exists($this, 'pageFilters')) {
            return $this->pageFilters ?? [];
        }

        return property_exists($this, 'filters') ? ($this->filters ?? []) : [];
    }
}