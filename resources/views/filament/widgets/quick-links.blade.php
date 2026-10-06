<x-filament-widgets::widget>
    <x-filament::section heading="Pintasan">
        <div class="flex flex-wrap gap-3">
            <x-filament::button tag="a" :href="route('pos')" icon="heroicon-o-calculator" size="xl">
                Buka Kasir (POS)
            </x-filament::button>

            <x-filament::button tag="a" :href="route('recommendation')" color="gray" icon="heroicon-o-sparkles" size="xl">
                Rekomendasi Produk
            </x-filament::button>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>