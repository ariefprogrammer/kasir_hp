<div x-data="{ filters: false }" class="min-h-screen pb-24">
    {{-- Notifikasi singkat --}}
    <div
        x-data="{ show: false, message: '', type: 'success', timer: null }"
        x-on:toast.window="message = $event.detail.message ?? $event.detail[0]?.message ?? ''; type = $event.detail.type ?? $event.detail[0]?.type ?? 'success'; show = true; clearTimeout(timer); timer = setTimeout(() => show = false, 2200)"
        x-show="show"
        style="display: none"
        :class="type === 'error' ? 'bg-red-600' : 'bg-green-600'"
        class="fixed inset-x-4 top-16 z-50 mx-auto max-w-sm rounded-lg px-4 py-3 text-center text-sm text-white shadow-lg"
    >
        <span x-text="message"></span>
    </div>

    <header class="sticky top-0 z-20 flex items-center justify-between gap-2 bg-white px-4 py-3 shadow">
        <h1 class="text-lg font-semibold">Rekomendasi</h1>
        <div class="flex items-center gap-4 text-sm">
            <span>{{ auth()->user()->name }}</span>
            <a href="{{ route('pos') }}" class="flex min-h-11 items-center gap-1 text-blue-600">
                Kasir
                @if ($this->cartCount > 0)
                    <span class="rounded-full bg-blue-600 px-2 py-0.5 text-xs text-white">{{ $this->cartCount }}</span>
                @endif
            </a>
            <a href="{{ url('/admin') }}" class="flex min-h-11 items-center text-blue-600">Panel</a>
        </div>
    </header>

    <div class="mx-auto grid max-w-7xl gap-4 p-4 lg:grid-cols-4">
        {{-- Filter --}}
        <aside class="lg:col-span-1">
            <button type="button" @click="filters = !filters"
                    class="mb-3 flex h-11 w-full items-center justify-center rounded-lg bg-white text-sm font-medium shadow lg:hidden">
                <span x-text="filters ? 'Sembunyikan filter' : 'Tampilkan filter'"></span>
            </button>

            <div :class="filters ? 'block' : 'hidden lg:block'" class="space-y-3 rounded-xl bg-white p-4 shadow">
                <label class="block text-sm">Kata kunci
                    <input type="search" wire:model.live.debounce.400ms="search" placeholder="Nama / model / merek"
                           class="mt-1 h-11 w-full rounded border border-gray-300 px-2 text-base">
                </label>

                <div>
                    <p class="text-sm">Rentang harga (Rp)</p>
                    <div class="mt-1 grid grid-cols-2 gap-2">
                        <input type="number" min="0" inputmode="numeric" wire:model.live.debounce.600ms="priceMin" placeholder="Min"
                               class="h-11 w-full rounded border border-gray-300 px-2 text-base">
                        <input type="number" min="0" inputmode="numeric" wire:model.live.debounce.600ms="priceMax" placeholder="Maks"
                               class="h-11 w-full rounded border border-gray-300 px-2 text-base">
                    </div>
                </div>

                @php
                    $selects = [
                        ['model' => 'ramMin', 'label' => 'RAM minimal', 'unit' => ' GB', 'options' => ['2', '3', '4', '6', '8', '12', '16']],
                        ['model' => 'storageMin', 'label' => 'Storage minimal', 'unit' => ' GB', 'options' => ['32', '64', '128', '256', '512', '1024']],
                        ['model' => 'screenMin', 'label' => 'Ukuran layar minimal', 'unit' => ' inci', 'options' => ['5.5', '6.0', '6.5', '6.7']],
                        ['model' => 'batteryMin', 'label' => 'Baterai minimal', 'unit' => ' mAh', 'options' => ['3000', '4000', '5000', '6000']],
                        ['model' => 'cameraMin', 'label' => 'Kamera minimal', 'unit' => ' MP', 'options' => ['8', '12', '48', '50', '64', '108']],
                    ];
                @endphp

                @foreach ($selects as $select)
                    <label class="block text-sm">{{ $select['label'] }}
                        <select wire:model.live="{{ $select['model'] }}" class="mt-1 h-11 w-full rounded border border-gray-300 bg-white px-2 text-base">
                            <option value="">Semua</option>
                            @foreach ($select['options'] as $option)
                                <option value="{{ $option }}">&ge; {{ $option }}{{ $select['unit'] }}</option>
                            @endforeach
                        </select>
                    </label>
                @endforeach

                <label class="block text-sm">Jaringan
                    <select wire:model.live="network" class="mt-1 h-11 w-full rounded border border-gray-300 bg-white px-2 text-base">
                        <option value="">Semua</option>
                        <option value="4G">4G</option>
                        <option value="5G">5G</option>
                    </select>
                </label>

                <label class="block text-sm">Sistem operasi
                    <select wire:model.live="os" class="mt-1 h-11 w-full rounded border border-gray-300 bg-white px-2 text-base">
                        <option value="">Semua</option>
                        @foreach ($this->osOptions as $osOption)
                            <option value="{{ $osOption }}">{{ $osOption }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block text-sm">Kondisi
                    <select wire:model.live="condition" class="mt-1 h-11 w-full rounded border border-gray-300 bg-white px-2 text-base">
                        <option value="">Semua</option>
                        <option value="baru">Baru</option>
                        <option value="second">Second</option>
                    </select>
                </label>

                @if ($this->brandOptions->isNotEmpty())
                    <div>
                        <p class="text-sm">Merek</p>
                        <div class="mt-1 flex flex-wrap gap-2">
                            @foreach ($this->brandOptions as $brand)
                                <label class="flex h-11 cursor-pointer items-center gap-2 rounded-lg border border-gray-300 px-3 text-sm">
                                    <input type="checkbox" wire:model.live="brands" value="{{ $brand }}">
                                    {{ $brand }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                <label class="flex min-h-11 items-center gap-2 text-sm">
                    <input type="checkbox" wire:model.live="showOutOfStock">
                    Tampilkan produk stok kosong
                </label>

                <button type="button" wire:click="resetFilters" class="h-11 w-full rounded-lg border border-gray-300 text-sm">
                    Reset filter
                </button>
            </div>
        </aside>

        {{-- Hasil --}}
        <section class="lg:col-span-3">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <p class="text-sm text-gray-600">{{ $this->total }} produk ditemukan</p>
                <label class="flex items-center gap-2 text-sm">
                    Urutkan
                    <div class="relative">
                        <select wire:model.live="sort" class="h-8 appearance-none rounded border border-gray-300 bg-white pl-3 pr-10 cursor-pointer">
                            <option value="price_asc">Harga termurah</option>
                            <option value="price_desc">Harga termahal</option>
                            <option value="ram_desc">RAM terbesar</option>
                            <option value="battery_desc">Baterai terbesar</option>
                        </select>
                        
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5">
                                <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                </label>
            </div>

            @error('compare')
                <p class="mb-2 text-sm text-red-600">{{ $message }}</p>
            @enderror

            <div class="grid grid-cols-2 gap-3 md:grid-cols-3">
                @forelse ($this->results['items'] as $product)
                    @php
                        $spec = $product->spec;
                        $specLine = collect([
                            $spec?->ram ? $spec->ram . ' GB RAM' : null,
                            $spec?->storage ? $spec->storage . ' GB' : null,
                            $spec?->network,
                            $spec?->battery ? $spec->battery . ' mAh' : null,
                            $spec?->screen_size ? $spec->screen_size . ' inci' : null,
                        ])->filter()->implode(' · ');
                        $inCompare = in_array($product->id, $compare, true);
                    @endphp

                    <div wire:key="rec-{{ $product->id }}" class="flex h-full flex-col overflow-hidden rounded-lg bg-white shadow">
                        @if ($product->photo)
                            <img src="{{ asset('storage/' . $product->photo) }}" alt="{{ $product->name }}" class="aspect-square w-full bg-gray-50 p-2 object-contain">
                        @else
                            <div class="flex aspect-square w-full items-center justify-center bg-gray-100 text-xs text-gray-400">Tanpa foto</div>
                        @endif

                        <div class="flex flex-1 flex-col p-3">
                            <p class="line-clamp-2 text-sm font-medium">{{ $product->name }}</p>
                            <p class="mt-1 text-xs text-gray-500">{{ $product->brand }} &middot; {{ ucfirst($product->condition) }}</p>
                            @if ($specLine !== '')
                                <p class="mt-1 text-xs text-gray-600">{{ $specLine }}</p>
                            @endif
                            
                            <div class="mt-auto pt-2">
                                <p class="font-semibold text-blue-700">{{ rupiah($product->sell_price) }}</p>
                                <p class="mt-1 text-xs {{ $product->stock <= 0 ? 'text-red-600' : ($product->stock <= 3 ? 'text-amber-600' : 'text-green-700') }}">
                                    {{ $product->stock <= 0 ? 'Stok habis' : 'Stok ' . $product->stock }}
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 p-3 pt-0">
                            <button type="button" wire:click="addToPos({{ $product->id }})" @disabled($product->stock < 1)
                                    class="col-span-2 h-11 rounded-lg bg-blue-600 text-sm font-medium text-white disabled:bg-gray-300">
                                Add to Cart
                            </button>
                            <button type="button" wire:click="showDetail({{ $product->id }})"
                                    class="h-11 rounded-lg border border-gray-300 text-sm">
                                Detail
                            </button>
                            <button type="button" wire:click="toggleCompare({{ $product->id }})"
                                    class="h-11 rounded-lg border text-sm {{ $inCompare ? 'border-blue-600 bg-blue-50 text-blue-700' : 'border-gray-300' }}">
                                {{ $inCompare ? 'Dipilih' : 'Bandingkan' }}
                            </button>
                        </div>
                    </div>
                @empty
                    <p class="col-span-full py-10 text-center text-gray-500">Tidak ada produk yang cocok dengan filter ini.</p>
                @endforelse
            </div>

            @if ($this->results['more'])
                <button type="button" wire:click="loadMore" class="mt-4 h-12 w-full rounded-lg bg-white text-sm font-medium shadow">
                    Tampilkan lebih banyak
                </button>
            @endif
        </section>
    </div>

    {{-- Bar perbandingan --}}
    @if (count($compare) > 0)
        <div class="fixed inset-x-0 bottom-0 z-30 flex items-center justify-between gap-2 border-t bg-white p-3">
            <span class="text-sm">{{ count($compare) }}/3 dipilih</span>
            <div class="flex gap-2">
                <button type="button" wire:click="clearCompare" class="h-11 px-3 text-sm">Kosongkan</button>
                <button type="button" wire:click="openCompare" @disabled(count($compare) < 2)
                        class="h-11 rounded-lg bg-blue-600 px-4 text-sm font-medium text-white disabled:bg-gray-300">
                    Bandingkan
                </button>
            </div>
        </div>
    @endif

    {{-- Modal perbandingan --}}
    @if ($showCompare)
        @php $comparison = $this->comparison; @endphp

        @if ($comparison['products']->count() >= 2)
            <div class="fixed inset-0 z-40 flex items-end justify-center bg-black/50 sm:items-center" wire:click.self="closeCompare">
                <div class="max-h-[90vh] w-full max-w-4xl overflow-auto rounded-t-2xl bg-white p-4 sm:rounded-2xl">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-base font-semibold">Bandingkan Produk</h2>
                        <button type="button" wire:click="closeCompare" class="h-11 w-11 text-xl">&times;</button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[480px] table-fixed break-words text-sm">
                            <thead>
                                <tr>
                                    <th class="w-24 sm:w-28"></th>
                                    
                                    @foreach ($comparison['products'] as $item)
                                        <th class="p-2 text-left align-top">
                                            @if ($item->photo)
                                                <img src="{{ asset('storage/' . $item->photo) }}" alt="{{ $item->name }}" class="mb-1 aspect-square w-full rounded bg-gray-50 p-1 object-contain">
                                            @endif
                                            <span class="line-clamp-2 font-medium">{{ $item->name }}</span>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($comparison['rows'] as $row)
                                    <tr class="border-t">
                                        <td class="p-2 text-gray-500">{{ $row['label'] }}</td>
                                        @foreach ($row['values'] as $i => $value)
                                            <td class="p-2 {{ in_array($i, $row['best'], true) ? 'font-semibold text-green-700' : '' }}">
                                                @if ($value === null || $value === '')
                                                    -
                                                @elseif ($row['format'] === 'money')
                                                    {{ rupiah($value) }}
                                                @else
                                                    {{ $value }}{{ $row['suffix'] }}
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                                <tr class="border-t">
                                    <td></td>
                                    @foreach ($comparison['products'] as $item)
                                        <td class="p-2">
                                            <button type="button" wire:click="addToPos({{ $item->id }})" @disabled($item->stock < 1)
                                                    class="h-11 w-full rounded-lg bg-blue-600 text-sm font-medium text-white disabled:bg-gray-300">
                                                Add to Cart
                                            </button>
                                        </td>
                                    @endforeach
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">Nilai terbaik per baris ditandai hijau.</p>
                </div>
            </div>
        @endif
    @endif

    {{-- Modal detail --}}
    @if ($this->detail)
        @php
            $detail = $this->detail;
            $detailProduct = $detail['product'];
        @endphp

        <div class="fixed inset-0 z-40 flex items-end justify-center bg-black/50 sm:items-center" wire:click.self="closeDetail">
            <div class="max-h-[90vh] w-full max-w-lg overflow-auto rounded-t-2xl bg-white p-4 sm:rounded-2xl">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-base font-semibold">{{ $detailProduct->name }}</h2>
                    <button type="button" wire:click="closeDetail" class="h-11 w-11 text-xl">&times;</button>
                </div>

                @if ($detailProduct->photo)
                    <img src="{{ asset('storage/' . $detailProduct->photo) }}" alt="{{ $detailProduct->name }}" class="mb-3 aspect-square max-h-72 w-full rounded-lg bg-gray-50 p-2 object-contain">
                @endif

                <table class="w-full text-sm">
                    <tbody>
                        @foreach ($detail['rows'] as $row)
                            @php $value = $row['values'][0] ?? null; @endphp
                            <tr class="border-t">
                                <td class="p-2 text-gray-500">{{ $row['label'] }}</td>
                                <td class="p-2">
                                    @if ($value === null || $value === '')
                                        -
                                    @elseif ($row['format'] === 'money')
                                        {{ rupiah($value) }}
                                    @else
                                        {{ $value }}{{ $row['suffix'] }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <button type="button" wire:click="addToPos({{ $detailProduct->id }})" @disabled($detailProduct->stock < 1)
                        class="mt-4 h-12 w-full rounded-lg bg-blue-600 font-medium text-white disabled:bg-gray-300">
                    Add to Cart
                </button>
            </div>
        </div>
    @endif
</div>