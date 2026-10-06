<?php

namespace App\Filament\Pages\Concerns;

use Filament\Actions\Action;
use Illuminate\Support\Arr;

trait HasExportActions
{
    abstract protected function exportKey(): string;

    protected function exportUrl(string $format): string
    {
        $filters = property_exists($this, 'filters') ? ($this->filters ?? []) : [];

        $params = array_filter(
            Arr::only($filters, ['preset', 'startDate', 'endDate', 'cashier_id', 'payment_method', 'category_id', 'product_id']),
            fn ($value) => filled($value)
        );

        return route('reports.export', ['report' => $this->exportKey(), 'format' => $format] + $params);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label('Ekspor Excel')
                ->icon('heroicon-o-table-cells')
                ->color('success')
                ->url(fn (): string => $this->exportUrl('xlsx'))
                ->openUrlInNewTab(),

            Action::make('exportPdf')
                ->label('Ekspor PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->url(fn (): string => $this->exportUrl('pdf'))
                ->openUrlInNewTab(),
        ];
    }
}