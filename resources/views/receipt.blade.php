<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Struk {{ $sale->invoice_no }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #f3f4f6;
            color: #000;
            font-family: ui-monospace, Menlo, Consolas, monospace;
        }
        .toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            justify-content: center;
            padding: 12px;
            background: #fff;
            border-bottom: 1px solid #d1d5db;
            font-family: system-ui, sans-serif;
        }
        .toolbar a, .toolbar button {
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            padding: 0 14px;
            border: 1px solid #9ca3af;
            border-radius: 8px;
            background: #fff;
            color: #111;
            font-size: 14px;
            text-decoration: none;
            cursor: pointer;
        }
        .toolbar .active { background: #2563eb; border-color: #2563eb; color: #fff; }
        .toolbar form {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            justify-content: center;
        }
        .toolbar label { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; }
        .toolbar select, .toolbar input[type=number] {
            min-height: 44px;
            padding: 0 8px;
            border: 1px solid #9ca3af;
            border-radius: 8px;
            font-size: 14px;
        }
        .toolbar input[type=number] { width: 80px; }
        .paper {
            width: {{ $paper['width'] }};
            max-width: 100%;
            margin: 16px auto;
            padding: {{ $paper['pad'] }};
            background: #fff;
            font-size: {{ $paper['font'] }}px;
            line-height: 1.4;
            box-shadow: 0 1px 4px rgba(0, 0, 0, .2);
        }
        .center { text-align: center; }
        .store { font-size: 1.2em; }
        .logo {
            display: block;
            max-width: 60%;
            max-height: 18mm;
            margin: 0 auto 4px;
            object-fit: contain;
            filter: grayscale(1) contrast(1.4);
        }
        .row { display: flex; justify-content: space-between; gap: 8px; }
        .row span:last-child { text-align: right; }
        .bold { font-weight: bold; }
        .item { margin-bottom: 4px; }
        hr { border: 0; border-top: 1px dashed #000; margin: 6px 0; }
        .void {
            margin-bottom: 6px;
            padding: 4px;
            border: 2px solid #000;
            text-align: center;
            font-weight: bold;
        }

        @page { size: {{ $paper['page'] }}; margin: {{ $paper['margin'] }}; }

        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
            .paper {
                width: 100%;
                margin: 0;
                padding: {{ $paper['print_pad'] }};
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        @foreach (['58' => '58 mm', '80' => '80 mm', 'a5' => 'A5'] as $key => $label)
            <a href="{{ route('receipt.show', ['sale' => $sale, 'size' => $key]) }}"
            class="{{ $type === $key ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
        <button type="button" onclick="window.print()">Cetak</button>
        <a href="{{ $sale->whatsappUrl() }}" target="_blank" rel="noopener">WhatsApp</a>
        <a href="{{ route('pos') }}">Kembali ke Kasir</a>
    </div>

    <div class="paper">
        @if ($sale->status === 'void')
            <div class="void">TRANSAKSI VOID</div>
        @endif

        <div class="center">
            @if ($setting->logoUrl())
                <img src="{{ $setting->logoUrl() }}" alt="" class="logo">
            @endif
            <strong class="store">{{ $setting->store_name }}</strong>
            @if ($setting->store_address)
                <div>{{ $setting->store_address }}</div>
            @endif
            @if ($setting->store_phone)
                <div>Telp. {{ $setting->store_phone }}</div>
            @endif
        </div>

        <hr>

        <div class="row"><span>No</span><span>{{ $sale->invoice_no }}</span></div>
        <div class="row"><span>Tanggal</span><span>{{ $sale->sold_at->format('d/m/Y H:i') }}</span></div>
        <div class="row"><span>Kasir</span><span>{{ $sale->cashier?->name }}</span></div>
        @if ($sale->customer_name)
            <div class="row"><span>Pembeli</span><span>{{ $sale->customer_name }}</span></div>
        @endif

        <hr>

        @foreach ($sale->items as $item)
            <div class="item">
                <div>{{ $item->product_name }}</div>
                @if ($item->imei)
                    <div>IMEI: {{ $item->imei }}</div>
                @endif
                @if ($item->imei2)
                    <div>IMEI 2: {{ $item->imei2 }}</div>
                @endif
                <div class="row">
                    <span>{{ $item->qty }} x {{ rupiah($item->deal_price) }}</span>
                    <span>{{ rupiah($item->subtotal) }}</span>
                </div>
            </div>
        @endforeach

        <hr>

        <div class="row bold"><span>TOTAL</span><span>{{ rupiah($sale->total) }}</span></div>
        <div class="row"><span>Bayar ({{ $sale->paymentLabel() }})</span><span>{{ rupiah($sale->paid_amount) }}</span></div>
        <div class="row"><span>Kembali</span><span>{{ rupiah($sale->change_amount) }}</span></div>

        <hr>

        <div class="center">{!! nl2br(e($setting->footer)) !!}</div>
    </div>
    @if ($autoPrint)
        <script>
            window.addEventListener('load', () => window.print());
        </script>
    @endif
</body>
</html>