<?php

namespace App\Livewire;

use App\Models\Product;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Models\Sale;
use App\Services\SaleService;
use InvalidArgumentException;
use App\Models\PaymentMethod;
use Illuminate\Validation\Rule;
use App\Models\ProductUnit;
use App\Support\PosCart;

#[Layout('layouts.pos')]
class Pos extends Component
{
    public string $search = '';

    /**
     * Hanya menyimpan product_id => qty & harga deal.
     * Nama, harga standar, dan stok SELALU dibaca ulang dari database,
     * karena properti publik Livewire bisa dimanipulasi dari browser.
     *
     * @var array<int, array{qty: int|string, deal_price: int|string}>
     */
    public array $cart = [];

    public string $paymentMethod = '';

    public string $paidAmount = '';

    public string $customerName = '';

    public string $customerPhone = '';

    public ?int $lastSaleId = null;

    public ?int $pickerProductId = null;

    public string $unitSearch = '';

    public function mount(): void
    {
        $user = auth()->user();

        abort_unless(
            $user && $user->is_active && $user->hasAnyRole(['owner', 'admin', 'kasir']),
            403
        );

        // Muat keranjang dari session (format lama otomatis dikonversi),
        // lalu buang produk nonaktif dan unit yang sudah tidak tersedia.
        $cart = PosCart::normalize(session('pos_cart', []));

        if (! empty($cart)) {
            $productIds = Product::query()
                ->where('is_active', true)
                ->whereIn('id', collect($cart)->pluck('product_id')->unique()->all())
                ->pluck('id')
                ->all();

            $unitIds = ProductUnit::query()
                ->available()
                ->whereIn('id', collect($cart)->pluck('unit_id')->filter()->all())
                ->pluck('id')
                ->all();

            $this->cart = collect($cart)
                ->filter(fn ($line) => in_array((int) $line['product_id'], $productIds)
                    && (empty($line['unit_id']) || in_array((int) $line['unit_id'], $unitIds)))
                ->all();
        }

        $this->paymentMethod = $this->defaultPaymentMethod();

        // Datang dari Rekomendasi untuk produk ber-IMEI: langsung buka pilihan unit.
        if ($pick = (int) request()->query('pilih')) {
            $this->selectProduct($pick);
        }
    }

    public function selectProduct(int $productId): void
    {
        $this->resetErrorBag('scan');

        $product = Product::query()
            ->select(['id', 'stock', 'track_imei'])
            ->withCount('availableUnits')
            ->where('is_active', true)
            ->find($productId);

        if (! $product || $product->stock < 1) {
            return;
        }

        if ($product->track_imei && $product->available_units_count > 0) {
            $this->pickerProductId = $product->id;
            $this->unitSearch = ctype_digit(trim($this->search)) ? trim($this->search) : '';

            return;
        }

        $this->addToCart($productId);
    }

    // Menambah baris "tanpa IMEI" (juga dipakai untuk produk biasa).
    public function addToCart(int $productId): void
    {
        $product = Product::query()
            ->select(['id', 'sell_price', 'stock'])
            ->withCount('availableUnits')
            ->where('is_active', true)
            ->find($productId);

        if (! $product || $product->stock < 1) {
            return;
        }

        $key = 'p' . $productId;
        $genericQty = (int) ($this->cart[$key]['qty'] ?? 0);

        if (PosCart::qtyForProduct($this->cart, $productId) + 1 > $product->stock) {
            $this->addError('scan', 'Stok tidak mencukupi.');

            return;
        }

        $untracked = max(0, $product->stock - $product->available_units_count);

        if ($genericQty + 1 > $untracked) {
            $this->addError('scan', 'Sisa stok tanpa IMEI sudah habis. Pilih unit ber-IMEI.');

            return;
        }

        $this->resetErrorBag('scan');

        $this->cart[$key] = [
            'product_id' => $productId,
            'unit_id' => null,
            'qty' => $genericQty + 1,
            'deal_price' => $this->cart[$key]['deal_price'] ?? $product->sell_price,
        ];

        $this->pickerProductId = null;
    }

    public function addUnit(int $unitId): void
    {
        $unit = ProductUnit::query()->available()->find($unitId);

        if (! $unit) {
            $this->addError('scan', 'IMEI ini sudah tidak tersedia.');

            return;
        }

        $product = Product::query()
            ->select(['id', 'sell_price', 'stock'])
            ->where('is_active', true)
            ->find($unit->product_id);

        if (! $product || $product->stock < 1) {
            $this->addError('scan', 'Produk tidak tersedia.');

            return;
        }

        $key = 'u' . $unit->id;

        if (isset($this->cart[$key])) {
            return;
        }

        if (PosCart::qtyForProduct($this->cart, $product->id) + 1 > $product->stock) {
            $this->addError('scan', 'Stok tidak mencukupi.');

            return;
        }

        $this->resetErrorBag('scan');

        $this->cart[$key] = [
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'qty' => 1,
            'deal_price' => $product->sell_price,
        ];
    }

    public function scan(): void
    {
        $code = strtoupper(trim($this->search));

        if ($code === '') {
            return;
        }

        $this->resetErrorBag('scan');

        // Cocokkan IMEI lebih dulu, baru SKU/barcode.
        $unit = ProductUnit::query()
            ->where(fn ($q) => $q->where('imei', $code)->orWhere('imei2', $code))
            ->first(['id', 'status']);

        if ($unit) {
            if ($unit->status !== 'tersedia') {
                $this->addError('scan', 'IMEI ini sudah terjual.');

                return;
            }

            $this->addUnit($unit->id);

            if (! $this->getErrorBag()->has('scan')) {
                $this->search = '';
            }

            return;
        }

        $product = Product::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->where('barcode', $code)->orWhere('sku', $code))
            ->first(['id']);

        if ($product) {
            $this->selectProduct($product->id);
            $this->search = '';
        }
    }

    public function closePicker(): void
    {
        $this->pickerProductId = null;
        $this->unitSearch = '';
        $this->resetErrorBag('scan');
    }

    public function removeFromCart(string $key): void
    {
        unset($this->cart[$key]);
    }

    public function dehydrate(): void
    {
        session(['pos_cart' => $this->cart]);
    }

    #[Computed]
    public function products(): Collection
    {
        return Product::query()
            ->select(['id', 'name', 'sku', 'brand', 'sell_price', 'stock', 'photo', 'track_imei'])
            ->withCount('availableUnits')
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->when($this->search !== '', function ($query) {
                $term = '%' . $this->search . '%';

                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', $term)
                        ->orWhere('sku', 'like', $term)
                        ->orWhere('barcode', 'like', $term)
                        ->orWhereHas('availableUnits', fn ($u) => $u
                            ->where('imei', 'like', $term)
                            ->orWhere('imei2', 'like', $term));
                });
            })
            ->orderBy('name')
            ->limit(24)
            ->get();
    }

    #[Computed]
    public function cartItems(): Collection
    {
        if (empty($this->cart)) {
            return collect();
        }

        $lines = collect($this->cart);

        $products = Product::query()
            ->select(['id', 'name', 'sku', 'sell_price', 'cost_price', 'stock', 'track_imei'])
            ->withCount('availableUnits')
            ->whereIn('id', $lines->pluck('product_id')->unique()->all())
            ->get()
            ->keyBy('id');

        $units = ProductUnit::query()
            ->whereIn('id', $lines->pluck('unit_id')->filter()->all())
            ->get()
            ->keyBy('id');

        $qtyByProduct = $lines
            ->groupBy('product_id')
            ->map(fn ($group) => $group->sum(fn ($line) => max(1, (int) $line['qty'])));

        return $lines
            ->map(function ($line, $key) use ($products, $units, $qtyByProduct) {
                $product = $products->get($line['product_id']);

                if (! $product) {
                    return null;
                }

                $isUnit = ! empty($line['unit_id']);
                $unit = $isUnit ? $units->get($line['unit_id']) : null;
                $qty = $isUnit ? 1 : max(1, (int) $line['qty']);
                $deal = max(0, (int) $line['deal_price']);
                $untracked = max(0, $product->stock - $product->available_units_count);

                $issue = null;

                if ($isUnit && (! $unit || $unit->status !== 'tersedia')) {
                    $issue = 'Unit IMEI ini sudah tidak tersedia, hapus dari keranjang.';
                } elseif (($qtyByProduct[$product->id] ?? 0) > $product->stock) {
                    $issue = 'Stok hanya ' . $product->stock . '.';
                } elseif (! $isUnit && $qty > $untracked) {
                    $issue = 'Sisa stok tanpa IMEI hanya ' . $untracked . '. Pilih unit ber-IMEI.';
                }

                return (object) [
                    'key' => (string) $key,
                    'id' => $product->id,
                    'unit_id' => $isUnit ? (int) $line['unit_id'] : null,
                    'is_unit' => $isUnit,
                    'tracks_imei' => (bool) $product->track_imei,
                    'imei' => $unit?->imei,
                    'imei2' => $unit?->imei2,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'stock' => $product->stock,
                    'standard_price' => $product->sell_price,
                    'qty' => $qty,
                    'deal_price' => $deal,
                    'subtotal' => $deal * $qty,
                    'diff' => ($product->sell_price - $deal) * $qty,
                    'below_min' => $deal < $product->minimumDealPrice(),
                    'issue' => $issue,
                ];
            })
            ->filter()
            ->values();
    }

    #[Computed]
    public function total(): int
    {
        return (int) $this->cartItems->sum('subtotal');
    }

    #[Computed]
    public function totalDiff(): int
    {
        return (int) $this->cartItems->sum('diff');
    }

    #[Computed]
    public function totalQty(): int
    {
        return (int) $this->cartItems->sum('qty');
    }

    #[Computed]
    public function change(): int
    {
        if (! $this->isCash) {
            return 0;
        }

        return max(0, (int) $this->paidAmount - $this->total);
    }

    #[Computed]
    public function canCheckout(): bool
    {
        if ($this->cartItems->isEmpty()) {
            return false;
        }

        $hasIssue = $this->cartItems->contains(
            fn ($item) => $item->below_min || $item->issue !== null || $item->deal_price <= 0
        );

        if ($hasIssue) {
            return false;
        }

        return $this->paymentMethod !== ''
            && (! $this->isCash || (int) $this->paidAmount >= $this->total);
    }

    #[Computed]
    public function pickerProduct(): ?Product
    {
        return $this->pickerProductId
            ? Product::query()->select(['id', 'name', 'stock'])->find($this->pickerProductId)
            : null;
    }

    #[Computed]
    public function pickerUnits(): Collection
    {
        if (! $this->pickerProductId) {
            return collect();
        }

        $inCart = collect($this->cart)->pluck('unit_id')->filter()->all();
        $term = trim($this->unitSearch);

        return ProductUnit::query()
            ->where('product_id', $this->pickerProductId)
            ->available()
            ->whereNotIn('id', $inCart)
            ->when($term !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('imei', 'like', "%{$term}%")
                ->orWhere('imei2', 'like', "%{$term}%")
                ->orWhere('note', 'like', "%{$term}%")))
            ->orderBy('imei')
            ->limit(50)
            ->get(['id', 'imei', 'imei2', 'note']);
    }

    #[Computed]
    public function pickerAllowGeneric(): bool
    {
        if (! $this->pickerProductId) {
            return false;
        }

        $product = Product::query()
            ->select(['id', 'stock'])
            ->withCount('availableUnits')
            ->find($this->pickerProductId);

        if (! $product) {
            return false;
        }

        $genericInCart = (int) collect($this->cart)
            ->where('product_id', $product->id)
            ->whereNull('unit_id')
            ->sum(fn ($line) => (int) $line['qty']);

        return ($product->stock - $product->available_units_count - $genericInCart) > 0;
    }

    #[Computed]
    public function lastSale(): ?Sale
    {
        if (! $this->lastSaleId) {
            return null;
        }

        return Sale::query()
            ->with('items', 'cashier:id,name')
            ->where('cashier_id', auth()->id())
            ->find($this->lastSaleId);
    }

    public function setExactAmount(): void
    {
        $this->paidAmount = (string) $this->total;
    }

    public function closeReceipt(): void
    {
        $this->lastSaleId = null;
    }

    private function defaultPaymentMethod(): string
    {
        return (string) array_key_first(PaymentMethod::activeOptions());
    }

    #[Computed]
    public function paymentMethods(): array
    {
        return PaymentMethod::activeOptions();
    }

    #[Computed]
    public function isCash(): bool
    {
        return (bool) PaymentMethod::query()
            ->where('code', $this->paymentMethod)
            ->value('is_cash');
    }

    public function checkout(SaleService $sales): void
    {
        $this->resetErrorBag('checkout');

        $this->validate([
            'paymentMethod' => ['required', Rule::in(array_keys(PaymentMethod::activeOptions()))],
            'paidAmount' => [Rule::requiredIf($this->isCash), 'integer', 'min:0'],
            'customerName' => ['nullable', 'string', 'max:100'],
            'customerPhone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s]+$/'],
        ]);

        $lines = collect($this->cart)
            ->map(fn ($line) => [
                'product_id' => (int) $line['product_id'],
                'unit_id' => ! empty($line['unit_id']) ? (int) $line['unit_id'] : null,
                'qty' => ! empty($line['unit_id']) ? 1 : max(1, (int) $line['qty']),
                'deal_price' => max(0, (int) $line['deal_price']),
            ])
            ->values()
            ->all();

        try {
            $sale = $sales->checkout(auth()->user(), $lines, [
                'payment_method' => $this->paymentMethod,
                'paid_amount' => (int) $this->paidAmount,
                'customer_name' => trim($this->customerName),
                'customer_phone' => trim($this->customerPhone),
            ]);
        } catch (InvalidArgumentException $e) {
            $this->addError('checkout', $e->getMessage());

            return;
        }

        $this->lastSaleId = $sale->id;
        $this->reset(['cart', 'paymentMethod', 'paidAmount', 'customerName', 'customerPhone','pickerProductId','unitSearch']);
        $this->paymentMethod = $this->defaultPaymentMethod();
        $this->dispatch('sale-completed');
    }

    public function render()
    {
        return view('livewire.pos');
    }
}