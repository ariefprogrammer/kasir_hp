<div x-data="{ open: false }" @sale-completed.window="open = false" class="min-h-screen pb-24 lg:pb-0">
    <header class="sticky top-0 z-20 flex items-center justify-between bg-white px-4 py-3 shadow">
        <h1 class="text-lg font-semibold">Kasir</h1>
        <div class="flex items-center gap-4 text-sm">
            <span>{{ auth()->user()->name }}</span>
            <a href="{{ route('recommendation') }}" class="flex min-h-11 items-center text-blue-600">Rekomendasi</a>
            <a href="{{ url('/admin') }}" class="flex min-h-11 items-center text-blue-600">Panel</a>
        </div>
    </header>

    @if ($this->lastSale)
        <div class="mx-auto mt-4 max-w-7xl px-4">
            <div class="flex items-center justify-between gap-3 rounded-lg bg-green-100 p-4 text-green-800">
                <div>
                    <p class="font-semibold">Transaksi berhasil: {{ $this->lastSale->invoice_no }}</p>
                    <p class="text-sm">
                        Total Rp {{ number_format($this->lastSale->total, 0, ',', '.') }}
                        &middot; Kembalian Rp {{ number_format($this->lastSale->change_amount, 0, ',', '.') }}
                    </p>
                </div>
                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    <a href="{{ route('receipt.show', $this->lastSale) }}" target="_blank"
                    class="flex h-11 items-center rounded-lg bg-green-700 px-3 text-sm font-medium text-white">
                        Cetak struk
                    </a>
                    <a href="{{ $this->lastSale->whatsappUrl() }}" target="_blank" rel="noopener"
                    class="flex h-11 items-center rounded-lg border border-green-700 px-3 text-sm font-medium">
                        WhatsApp
                    </a>
                    <button type="button" wire:click="closeReceipt" class="h-11 px-3 font-medium">Tutup</button>
                </div>
            </div>
        </div>
    @endif

    <div class="mx-auto grid max-w-7xl gap-4 p-4 lg:grid-cols-3">
        {{-- Katalog --}}
        <section class="lg:col-span-2">
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                wire:keydown.enter="scan"
                placeholder="Cari nama / SKU / barcode..."
                autofocus
                class="mb-4 h-12 w-full rounded-lg border border-gray-300 bg-white px-4 text-base"
            >

            <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-4">
                @forelse ($this->products as $product)
                    <button
                        type="button"
                        wire:key="product-{{ $product->id }}"
                        wire:click="addToCart({{ $product->id }})"
                        class="flex h-full flex-col overflow-hidden rounded-lg bg-white text-left shadow hover:ring-2 hover:ring-blue-500"
                    >
                        @if ($product->photo)
                            {{-- Gunakan aspect-square, object-contain, bg-gray-50 dan p-2 --}}
                            <img src="{{ asset('storage/' . $product->photo) }}" alt="{{ $product->name }}" class="aspect-square w-full bg-gray-50 p-2 object-contain">
                        @else
                            {{-- Samakan ukuran area kosong dengan aspect-square --}}
                            <div class="flex aspect-square w-full items-center justify-center bg-gray-100 text-xs text-gray-400">Tanpa foto</div>
                        @endif
                        
                        {{-- AREA TEKS --}}
                        {{-- Tambahkan flex-1 dan flex-col agar area ini mengisi sisa tinggi card --}}
                        <div class="flex flex-1 flex-col p-3">
                            <p class="line-clamp-2 text-sm font-medium">{{ $product->name }}</p>
                            <p class="mt-1 text-xs text-gray-500">{{ $product->sku }} &middot; stok {{ $product->stock }}</p>
                            
                            {{-- Tambahkan mt-auto agar harga selalu terdorong ke posisi paling bawah card --}}
                            <div class="mt-auto pt-2">
                                <p class="font-semibold text-blue-700">Rp {{ number_format($product->sell_price, 0, ',', '.') }}</p>
                            </div>
                        </div>
                    </button>
                @empty
                    <p class="col-span-full py-10 text-center text-gray-500">Produk tidak ditemukan.</p>
                @endforelse
            </div>
        </section>

        {{-- Keranjang: panel kanan di desktop, bottom sheet di HP --}}
        <aside
            :class="open ? 'translate-y-0' : 'translate-y-full lg:translate-y-0'"
            class="fixed inset-x-0 bottom-0 z-30 max-h-[85vh] overflow-y-auto rounded-t-2xl bg-white p-4 shadow-2xl transition-transform lg:sticky lg:top-20 lg:z-0 lg:max-h-[calc(100vh-6rem)] lg:self-start lg:rounded-xl lg:shadow"
        >
            <div class="mb-2 flex items-center justify-between">
                <h2 class="text-base font-semibold">Keranjang</h2>
                <button type="button" @click="open = false" class="h-11 w-11 text-xl lg:hidden">&times;</button>
            </div>

            @forelse ($this->cartItems as $item)
                <div wire:key="cart-{{ $item->id }}" class="border-b py-3">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="font-medium">{{ $item->name }}</p>
                            <p class="text-xs text-gray-500">
                                Standar: Rp {{ number_format($item->standard_price, 0, ',', '.') }}
                            </p>
                        </div>
                        <button type="button" wire:click="removeFromCart({{ $item->id }})" class="h-11 w-11 shrink-0 text-red-600">&times;</button>
                    </div>

                    <div class="mt-2 grid grid-cols-2 gap-2">
                        <label class="text-xs text-gray-600">Qty
                           <input
                                type="number" min="1" inputmode="numeric"
                                wire:model.live.debounce.500ms="cart.{{ $item->id }}.qty"
                                class="mt-1 h-11 w-full rounded border border-gray-300 px-2 text-base"
                            >
                        </label>
                        <label class="text-xs text-gray-600">Harga deal
                            <input
                                type="number" min="0" inputmode="numeric"
                                wire:model.live.debounce.500ms="cart.{{ $item->id }}.deal_price"
                                class="mt-1 h-11 w-full rounded border border-gray-300 px-2 text-base"
                            >
                        </label>
                    </div>

                    @if ($item->qty > $item->stock)
                        <p class="mt-1 text-xs text-red-600">Stok hanya {{ $item->stock }}.</p>
                    @endif
                    @if ($item->below_min)
                        <p class="mt-1 text-xs text-red-600">Harga deal terlalu rendah, tidak boleh di bawah batas minimum.</p>
                    @endif
                    @error("cart.{$item->id}.qty")
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror

                    <div class="mt-2 flex justify-between text-sm">
                        @if ($item->diff !== 0)
                            <span class="{{ $item->diff > 0 ? 'text-orange-600' : 'text-green-600' }}">
                                {{ $item->diff > 0 ? 'Diskon' : 'Markup' }}
                                Rp {{ number_format(abs($item->diff), 0, ',', '.') }}
                            </span>
                        @else
                            <span></span>
                        @endif
                        <span class="font-semibold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center py-10 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mb-3 h-12 w-12">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                    </svg>
                    
                    <p class="text-sm">Keranjang kosong.</p>
                </div>
            @endforelse

            <div class="mt-4 space-y-1">
                @if ($this->totalDiff !== 0)
                    <div class="flex justify-between text-sm text-gray-600">
                        <span>Total selisih nego</span>
                        <span>Rp {{ number_format($this->totalDiff, 0, ',', '.') }}</span>
                    </div>
                @endif
                <div class="flex justify-between text-lg font-bold">
                    <span>Total</span>
                    <span>Rp {{ number_format($this->total, 0, ',', '.') }}</span>
                </div>
            </div>

            @if ($this->cartItems->isNotEmpty())
                <div class="mt-4 space-y-3 border-t pt-4">
                    <div class="grid grid-cols-3 gap-2">
                        @foreach (['tunai' => 'Tunai', 'transfer' => 'Transfer', 'qris' => 'QRIS'] as $value => $label)
                            <label class="flex h-11 cursor-pointer items-center justify-center rounded-lg border border-gray-300 text-sm has-[:checked]:border-blue-600 has-[:checked]:bg-blue-600 has-[:checked]:text-white">
                                <input type="radio" wire:model.live="paymentMethod" value="{{ $value }}" class="sr-only">
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>

                    @if ($paymentMethod === 'tunai')
                        <div>
                            <label class="text-xs text-gray-600">Uang diterima</label>
                            <div class="mt-1 flex gap-2">
                                <input
                                    type="number" min="0" inputmode="numeric"
                                    wire:model.live.debounce.500ms="paidAmount"
                                    class="h-11 min-w-0 flex-1 rounded border border-gray-300 px-2 text-base"
                                >
                                <button type="button" wire:click="setExactAmount" class="h-11 shrink-0 rounded-lg border border-gray-300 px-3 text-sm">
                                    Uang pas
                                </button>
                            </div>
                            @error('paidAmount')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror

                            @if ((int) $paidAmount < $this->total)
                                <p class="mt-1 text-sm text-red-600">
                                    Kurang Rp {{ number_format($this->total - (int) $paidAmount, 0, ',', '.') }}
                                </p>
                            @else
                                <p class="mt-1 text-sm">
                                    Kembalian: <strong>Rp {{ number_format($this->change, 0, ',', '.') }}</strong>
                                </p>
                            @endif
                        </div>
                    @endif

                    <input type="text" wire:model="customerName" placeholder="Nama pembeli (opsional)"
                        class="h-11 w-full rounded border border-gray-300 px-2 text-base">
                    <input type="tel" wire:model="customerPhone" placeholder="No. HP pembeli (opsional)"
                        class="h-11 w-full rounded border border-gray-300 px-2 text-base">
                    @error('customerPhone')
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror

                    @error('checkout')
                        <p class="rounded bg-red-100 p-2 text-sm text-red-700">{{ $message }}</p>
                    @enderror

                    <button
                        type="button"
                        wire:click="checkout"
                        wire:loading.attr="disabled"
                        wire:target="checkout"
                        @disabled(! $this->canCheckout)
                        class="h-12 w-full rounded-lg bg-blue-600 font-semibold text-white disabled:cursor-not-allowed disabled:bg-gray-300"
                    >
                        Bayar Rp {{ number_format($this->total, 0, ',', '.') }}
                    </button>
                </div>
            @endif
        </aside>
    </div>

    {{-- Bar bawah khusus HP --}}
    <div x-show="!open" class="fixed inset-x-0 bottom-0 z-20 border-t bg-white p-3 lg:hidden">
        <button type="button" @click="open = true" class="flex h-12 w-full items-center justify-between rounded-lg bg-blue-600 px-4 text-white">
            <span>Keranjang ({{ $this->totalQty }})</span>
            <span>Rp {{ number_format($this->total, 0, ',', '.') }}</span>
        </button>
    </div>
</div>