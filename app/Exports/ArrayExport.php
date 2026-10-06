<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ArrayExport implements FromArray, ShouldAutoSize, WithColumnFormatting, WithStyles
{
    public function __construct(private array $data)
    {
    }

    public function array(): array
    {
        $rows = [
            [$this->data['title']],
            [$this->data['period']],
            [],
            $this->data['headings'],
        ];

        foreach ($this->data['rows'] as $row) {
            $rows[] = $row;
        }

        if (! empty($this->data['footer'])) {
            $rows[] = $this->data['footer'];
        }

        return $rows;
    }

    public function columnFormats(): array
    {
        $formats = [];

        foreach ($this->data['money'] ?? [] as $index) {
            $formats[Coordinate::stringFromColumnIndex($index + 1)] = '#,##0';
        }

        return $formats;
    }

    public function styles(Worksheet $sheet): array
    {
        $bold = ['font' => ['bold' => true]];

        // Baris 1 = judul, baris 4 = header tabel, data mulai baris 5.
        $styles = [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            4 => $bold,
        ];

        foreach ($this->data['bold'] ?? [] as $index) {
            $styles[5 + $index] = $bold;
        }

        if (! empty($this->data['footer'])) {
            $styles[5 + count($this->data['rows'])] = $bold;
        }

        return $styles;
    }
}