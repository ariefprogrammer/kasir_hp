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

    public string $paymentMethod = 'tunai';

    public string $paidAmount = '';

    public string $customerName = '';

    public string $customerPhone = '';

    public ?int $lastSaleId = null;

    public function mount(): void
    {
        $user = auth()->user();

        abort_unless(
            $user && $user->is_active && $user->hasAnyRole(['owner', 'admin', 'kasir']),
            403
        );

        // Muat keranjang dari session (mis. hasil "Tambah ke POS" di halaman Rekomendasi),
        // dan buang produk yang sudah tidak aktif atau terhapus.
        $cart = session('pos_cart', []);

        if (! empty($cart)) {
            $validIds = Product::query()
                ->where('is_active', true)
                ->whereIn('id', array_keys($cart))
                ->pluck('id')
                ->all();

            $this->cart = array_intersect_key($cart, array_flip($validIds));
        }
    }

    public function dehydrate(): void
    {
        session(['pos_cart' => $this->cart]);
    }

    #[Computed]
    public function products(): Collection
    {
        return Product::query()
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->when($this->search !== '', function ($query) {
                $term = '%' . $this->search . '%';

                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', $term)
                        ->orWhere('sku', 'like', $term)
                        ->orWhere('barcode', 'like', $term);
                });
            })
            ->orderBy('name')
            ->limit(24)
            ->get(['id', 'name', 'sku', 'brand', 'sell_price', 'stock', 'photo']);
    }

    #[Computed]
    public function cartItems(): Collection
    {
        if (empty($this->cart)) {
            return collect();
        }

        $ids = array_keys($this->cart);

        return Product::query()
            ->whereIn('id', $ids)
            ->get(['id', 'name', 'sku', 'sell_price', 'cost_price', 'stock'])
            ->map(function (Product $product) {
                $line = $this->cart[$product->id];
                $qty = max(1, (int) $line['qty']);
                $deal = max(0, (int) $line['deal_price']);

                return (object) [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'stock' => $product->stock,
                    'standard_price' => $product->sell_price,
                    'qty' => $qty,
                    'deal_price' => $deal,
                    'subtotal' => $deal * $qty,
                    'diff' => ($product->sell_price - $deal) * $qty,
                    'below_min' => $deal < $product->minimumDealPrice(),
                ];
            })
            ->sortBy(fn ($item) => array_search($item->id, $ids))
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

    public function addToCart(int $productId): void
    {
        $product = Product::query()
            ->where('is_active', true)
            ->find($productId, ['id', 'sell_price', 'stock']);

        if (! $product || $product->stock < 1) {
            return;
        }

        $currentQty = (int) ($this->cart[$productId]['qty'] ?? 0);

        if ($currentQty + 1 > $product->stock) {
            $this->addError("cart.{$productId}.qty", 'Stok tidak mencukupi.');

            return;
        }

        $this->cart[$productId] = [
            'qty' => $currentQty + 1,
            'deal_price' => $this->cart[$productId]['deal_price'] ?? $product->sell_price,
        ];
    }

    public function scan(): void
    {
        $code = trim($this->search);

        if ($code === '') {
            return;
        }

        $product = Product::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->where('barcode', $code)->orWhere('sku', $code))
            ->first(['id']);

        if ($product) {
            $this->addToCart($product->id);
            $this->search = '';
        }
    }

    public function removeFromCart(int $productId): void
    {
        unset($this->cart[$productId]);
    }

    #[Computed]
    public function change(): int
    {
        if ($this->paymentMethod !== 'tunai') {
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
            fn ($item) => $item->below_min || $item->qty > $item->stock || $item->deal_price <= 0
        );

        if ($hasIssue) {
            return false;
        }

        return $this->paymentMethod !== 'tunai' || (int) $this->paidAmount >= $this->total;
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

    public function checkout(SaleService $sales): void
    {
        $this->resetErrorBag('checkout');

        $this->validate([
            'paymentMethod' => ['required', 'in:tunai,transfer,qris'],
            'paidAmount' => ['required_if:paymentMethod,tunai', 'integer', 'min:0'],
            'customerName' => ['nullable', 'string', 'max:100'],
            'customerPhone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s]+$/'],
        ]);

        $lines = collect($this->cart)
            ->map(fn ($line, $id) => [
                'product_id' => (int) $id,
                'qty' => max(1, (int) $line['qty']),
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
        $this->reset(['cart', 'paymentMethod', 'paidAmount', 'customerName', 'customerPhone']);
        $this->dispatch('sale-completed');
    }

    public function render()
    {
        return view('livewire.pos');
    }
}