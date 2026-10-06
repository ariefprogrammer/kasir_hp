<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function afterCreate(): void
    {
        $product = $this->record;

        if ($product->stock > 0) {
            $product->stockMovements()->create([
                'type' => 'initial',
                'qty' => $product->stock,
                'user_id' => auth()->id(),
                'note' => 'Stok awal',
            ]);
        }
    }
}
