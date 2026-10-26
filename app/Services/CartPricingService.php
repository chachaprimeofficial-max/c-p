<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

class CartPricingService
{
    public function lines(array $cart, CurrencyService $currencies): array
    {
        $products = Product::with(['media', 'priceTiers'])->whereIn('id', array_keys($cart))->where('status', 'published')->get()->keyBy('id');
        $lines = [];
        foreach ($cart as $productId => $quantity) {
            $product = $products->get($productId);
            if (!$product) continue;
            $quantity = max(1, (int) $quantity);
            $tier = null;
            if ($product->b2b_enabled) {
                $tier = $product->priceTiers->first(fn ($item) => $quantity >= $item->min_quantity && ($item->max_quantity === null || $quantity <= $item->max_quantity));
            }
            $sourceCurrency = $tier?->currency ?? $product->base_currency ?? 'USD';
            $sourcePrice = $tier ? (float) $tier->unit_price : (float) $product->retail_price;
            $converted = $currencies->convert($sourcePrice, $sourceCurrency, $currencies->currentCurrency());
            $lines[] = [
                'product' => $product, 'quantity' => $quantity,
                'unit_price' => $converted['amount'], 'line_total' => round($converted['amount'] * $quantity, 2),
                'currency' => $converted['currency'], 'converted' => $converted['converted'],
            ];
        }
        return $lines;
    }
}
