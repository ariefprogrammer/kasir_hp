<?php

namespace App\Support;

use Carbon\Carbon;

class DateRange
{
    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function resolve(?array $filters): array
    {
        $preset = $filters['preset'] ?? 'month';
        $now = now();

        if ($preset === 'today') {
            return [$now->copy()->startOfDay(), $now->copy()->endOfDay()];
        }

        if ($preset === 'week') {
            return [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()];
        }

        if ($preset === 'custom') {
            $from = filled($filters['startDate'] ?? null)
                ? Carbon::parse($filters['startDate'])->startOfDay()
                : $now->copy()->startOfDay();

            $until = filled($filters['endDate'] ?? null)
                ? Carbon::parse($filters['endDate'])->endOfDay()
                : $now->copy()->endOfDay();

            // Kalau tanggal terbalik, tukar otomatis.
            return $from->greaterThan($until)
                ? [$until->copy()->startOfDay(), $from->copy()->endOfDay()]
                : [$from, $until];
        }

        return [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()];
    }
}