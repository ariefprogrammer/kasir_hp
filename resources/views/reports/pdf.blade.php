@php
    $fmt = function ($value, bool $isMoney) {
        if (is_int($value) || is_float($value)) {
            return $isMoney ? rupiah($value) : number_format($value, 0, ',', '.');
        }

        return $value;
    };
    $money = $money ?? [];
    $bold = $bold ?? [];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #111; }
        h1 { font-size: 15px; margin: 0 0 2px; }
        .store { font-size: 11px; color: #444; margin-bottom: 2px; }
        .meta { color: #555; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 4px 5px; vertical-align: top; }
        th { background: #eee; text-align: left; }
        .num { text-align: right; white-space: nowrap; }
        .bold td { font-weight: bold; background: #f4f4f4; }
    </style>
</head>
<body>
    <div class="store">{{ \App\Models\ReceiptSetting::current()->store_name }}</div>
    <h1>{{ $title }}</h1>
    <div class="meta">{{ $period }}</div>

    <table>
        <thead>
            <tr>
                @foreach ($headings as $heading)
                    <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $i => $row)
                <tr class="{{ in_array($i, $bold, true) ? 'bold' : '' }}">
                    @foreach ($row as $j => $cell)
                        <td class="{{ is_int($cell) || is_float($cell) ? 'num' : '' }}">{{ $fmt($cell, in_array($j, $money, true)) }}</td>
                    @endforeach
                </tr>
            @endforeach

            @if (! empty($footer))
                <tr class="bold">
                    @foreach ($footer as $j => $cell)
                        <td class="{{ is_int($cell) || is_float($cell) ? 'num' : '' }}">{{ $fmt($cell, in_array($j, $money, true)) }}</td>
                    @endforeach
                </tr>
            @endif
        </tbody>
    </table>
</body>
</html>