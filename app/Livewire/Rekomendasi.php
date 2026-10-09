<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\ProductSpec;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Support\PosCart;
use App\Models\Category;

#[Layout('layouts.pos')]
class Rekomendasi extends Component
{
    public string $search = '';

    public ?int $categoryId = null;

    public string $priceMin = '';

    public string $priceMax = '';

    public string $ramMin = '';

    public string $storageMin = '';

    public string $screenMin = '';

    public string $batteryMin = '';

    public string $cameraMin = '';

    public string $network = '';

    public string $os = '';

    public string $condition = '';

    /** @var array<int, string> */
    public array $brands = [];

    public bool $showOutOfStock = false;

    public string $sort = 'price_asc';

    public int $perPage = 24;

    /** @var array<int, int> */
    public array $compare = [];

    public bool $showCompare = false;

    public ?int $detailId = null;

    public function mount(): void
    {
        $user = auth()->user();

        abort_unless(
            $user && $user->is_active && $user->hasAnyRole(['owner', 'admin', 'kasir']),
            403
        );
    }

    public function updated(string $name): void
    {
        $uiOnly = ['perPage', 'showCompare', 'detailId'];

        if (! in_array($name, $uiOnly, true) && ! str_starts_with($name, 'compare')) {
            $this->perPage = 24;
        }
    }

    private function baseQuery(): Builder
    {
        $query = Product::query()
            ->leftJoin('product_specs', 'product_specs.product_id', '=', 'products.id')
            ->where('products.is_active', true);

        if (! $this->showOutOfStock) {
            $query->where('products.stock', '>', 0);
        }

        if (trim($this->search) !== '') {
            $term = '%' . trim($this->search) . '%';

            $query->where(function (Builder $q) use ($term) {
                $q->where('products.name', 'like', $term)
                    ->orWhere('products.brand', 'like', $term);
            });
        }

        if ($this->categoryId) {
            $query->where('products.category_id', $this->categoryId);
        }

        if ((int) $this->priceMin > 0) {
            $query->where('products.sell_price', '>=', (int) $this->priceMin);
        }

        if ((int) $this->priceMax > 0) {
            $query->where('products.sell_price', '<=', (int) $this->priceMax);
        }

        if (! empty($this->brands)) {
            $query->whereIn('products.brand', $this->brands);
        }

        if (in_array($this->condition, ['baru', 'second'], true)) {
            $query->where('products.condition', $this->condition);
        }

        $minimums = [
            'ramMin' => 'ram',
            'storageMin' => 'storage',
            'batteryMin' => 'battery',
            'cameraMin' => 'camera_mp',
        ];

        foreach ($minimums as $property => $column) {
            if ((int) $this->{$property} > 0) {
                $query->where("product_specs.{$column}", '>=', (int) $this->{$property});
            }
        }

        if ((float) $this->screenMin > 0) {
            $query->where('product_specs.screen_size', '>=', (float) $this->screenMin);
        }

        if (in_array($this->network, ['4G', '5G'], true)) {
            $query->where('product_specs.network', $this->network);
        }

        if ($this->os !== '') {
            $query->where('product_specs.os', $this->os);
        }

        return $query;
    }

    #[Computed]
    public function results(): array
    {
        // Kolom dipilih eksplisit: cost_price sengaja tidak ikut.
        $query = $this->baseQuery()->select([
            'products.id',
            'products.name',
            'products.brand',
            'products.sku',
            'products.sell_price',
            'products.stock',
            'products.photo',
            'products.condition',
        ]);

        match ($this->sort) {
            'price_desc' => $query->orderByDesc('products.sell_price'),
            'ram_desc' => $query->orderByDesc('product_specs.ram')->orderBy('products.sell_price'),
            'battery_desc' => $query->orderByDesc('product_specs.battery')->orderBy('products.sell_price'),
            default => $query->orderBy('products.sell_price'),
        };

        $rows = $query->with('spec')->limit($this->perPage + 1)->get();

        return [
            'items' => $rows->take($this->perPage),
            'more' => $rows->count() > $this->perPage,
        ];
    }

    #[Computed]
    public function total(): int
    {
        return $this->baseQuery()->count();
    }

    #[Computed]
    public function brandOptions(): Collection
    {
        return Product::query()
            ->where('is_active', true)
            ->whereNotNull('brand')
            ->where('brand', '!=', '')
            ->distinct()
            ->orderBy('brand')
            ->pluck('brand');
    }

    #[Computed]
    public function osOptions(): Collection
    {
        return ProductSpec::query()
            ->whereNotNull('os')
            ->where('os', '!=', '')
            ->distinct()
            ->orderBy('os')
            ->pluck('os');
    }

    #[Computed]
    public function cartCount(): int
    {
        return (int) collect(session('pos_cart', []))->sum('qty');
    }

    /**
     * Daftar baris untuk tampilan detail dan perbandingan.
     */
    private function definitions(): array
    {
        return [
            ['label' => 'Harga standar', 'get' => fn ($p) => $p->sell_price, 'format' => 'money', 'best' => 'low'],
            ['label' => 'Stok', 'get' => fn ($p) => $p->stock, 'format' => 'plain', 'best' => null],
            ['label' => 'Kondisi', 'get' => fn ($p) => ucfirst($p->condition), 'format' => 'plain', 'best' => null],
            ['label' => 'Merek', 'get' => fn ($p) => $p->brand, 'format' => 'plain', 'best' => null],
            ['label' => 'RAM', 'get' => fn ($p) => $p->spec?->ram, 'format' => 'plain', 'suffix' => ' GB', 'best' => 'high'],
            ['label' => 'Storage', 'get' => fn ($p) => $p->spec?->storage, 'format' => 'plain', 'suffix' => ' GB', 'best' => 'high'],
            ['label' => 'Ukuran layar', 'get' => fn ($p) => $p->spec?->screen_size, 'format' => 'plain', 'suffix' => ' inci', 'best' => null],
            ['label' => 'Baterai', 'get' => fn ($p) => $p->spec?->battery, 'format' => 'plain', 'suffix' => ' mAh', 'best' => 'high'],
            ['label' => 'Kamera utama', 'get' => fn ($p) => $p->spec?->camera_mp, 'format' => 'plain', 'suffix' => ' MP', 'best' => 'high'],
            ['label' => 'Chipset', 'get' => fn ($p) => $p->spec?->chipset, 'format' => 'plain', 'best' => null],
            ['label' => 'Sistem operasi', 'get' => fn ($p) => $p->spec?->os, 'format' => 'plain', 'best' => null],
            ['label' => 'Jaringan', 'get' => fn ($p) => $p->spec?->network, 'format' => 'plain', 'best' => null],
            ['label' => 'Warna', 'get' => fn ($p) => $p->spec?->color, 'format' => 'plain', 'best' => null],
        ];
    }

    private function buildRows(Collection $products): array
    {
        return collect($this->definitions())->map(function (array $definition) use ($products) {
            $values = $products->map(fn ($product) => $definition['get']($product))->all();
            $best = [];

            if ($definition['best']) {
                $numeric = array_filter($values, fn ($value) => is_numeric($value));

                if (count($numeric) > 1 && count(array_unique($numeric)) > 1) {
                    $target = $definition['best'] === 'high' ? max($numeric) : min($numeric);
                    $best = array_keys(array_filter($values, fn ($value) => is_numeric($value) && $value == $target));
                }
            }

            return [
                'label' => $definition['label'],
                'format' => $definition['format'],
                'suffix' => $definition['suffix'] ?? '',
                'values' => $values,
                'best' => $best,
            ];
        })->all();
    }

    private function loadProducts(array $ids): Collection
    {
        return Product::query()
            ->with('spec')
            ->where('is_active', true)
            ->whereIn('id', $ids)
            ->get(['id', 'name', 'brand', 'sell_price', 'stock', 'photo', 'condition'])
            ->sortBy(fn ($product) => array_search($product->id, $ids))
            ->values();
    }

    #[Computed]
    public function comparison(): array
    {
        $products = $this->loadProducts(array_slice($this->compare, 0, 3));

        return [
            'products' => $products,
            'rows' => $this->buildRows($products),
        ];
    }

    #[Computed]
    public function detail(): ?array
    {
        if (! $this->detailId) {
            return null;
        }

        $products = $this->loadProducts([$this->detailId]);

        if ($products->isEmpty()) {
            return null;
        }

        return [
            'product' => $products->first(),
            'rows' => $this->buildRows($products),
        ];
    }

    public function resetFilters(): void
    {
        $this->reset([
            'search', 'categoryId', 'priceMin', 'priceMax', 'ramMin', 'storageMin', 'screenMin',
            'batteryMin', 'cameraMin', 'network', 'os', 'condition', 'brands',
            'showOutOfStock', 'perPage',
        ]);
    }

    public function loadMore(): void
    {
        $this->perPage += 24;
    }

    public function selectCategory(?int $categoryId = null): void
    {
        // Klik kategori yang sama lagi = batalkan filter
        $this->categoryId = $this->categoryId === $categoryId ? null : $categoryId;
        $this->perPage = 24;
    }

    #[Computed]
    public function categories(): Collection
    {
        return Category::query()
            ->select(['id', 'name'])
            ->withCount(['products as products_count' => function ($q) {
                $q->where('is_active', true);

                if (! $this->showOutOfStock) {
                    $q->where('stock', '>', 0);
                }
            }])
            ->orderBy('name')
            ->get();
    }

    public function toggleCompare(int $productId): void
    {
        $this->resetErrorBag('compare');

        if (in_array($productId, $this->compare, true)) {
            $this->compare = array_values(array_diff($this->compare, [$productId]));

            return;
        }

        if (count($this->compare) >= 3) {
            $this->addError('compare', 'Maksimal 3 produk untuk dibandingkan.');

            return;
        }

        $this->compare[] = $productId;
    }

    public function clearCompare(): void
    {
        $this->compare = [];
        $this->showCompare = false;
    }

    public function openCompare(): void
    {
        if (count($this->compare) >= 2) {
            $this->showCompare = true;
        }
    }

    public function closeCompare(): void
    {
        $this->showCompare = false;
    }

    public function showDetail(int $productId): void
    {
        $this->detailId = $productId;
    }

    public function closeDetail(): void
    {
        $this->detailId = null;
    }

    public function addToPos(int $productId): void
    {
        $product = Product::query()
            ->select(['id', 'name', 'sell_price', 'stock', 'track_imei'])
            ->withCount('availableUnits')
            ->where('is_active', true)
            ->find($productId);

        if (! $product || $product->stock < 1) {
            $this->dispatch('toast', message: 'Produk tidak tersedia.', type: 'error');

            return;
        }

        // Produk ber-IMEI: pilih unitnya di halaman POS.
        if ($product->track_imei && $product->available_units_count > 0) {
            $this->redirect(route('pos', ['pilih' => $product->id]));

            return;
        }

        $cart = PosCart::normalize(session('pos_cart', []));
        $key = 'p' . $productId;
        $current = (int) ($cart[$key]['qty'] ?? 0);

        if (PosCart::qtyForProduct($cart, $productId) + 1 > $product->stock) {
            $this->dispatch('toast', message: 'Stok tidak mencukupi.', type: 'error');

            return;
        }

        $cart[$key] = [
            'product_id' => $productId,
            'unit_id' => null,
            'qty' => $current + 1,
            'deal_price' => $cart[$key]['deal_price'] ?? $product->sell_price,
        ];

        session(['pos_cart' => $cart]);

        $this->dispatch('toast', message: $product->name . ' ditambahkan ke keranjang POS.', type: 'success');
    }

    public function render()
    {
        return view('livewire.rekomendasi');
    }
}