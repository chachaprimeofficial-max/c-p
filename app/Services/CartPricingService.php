<?php

namespace App\Services;

use App\Models\Product;

class CartPricingService
{
    public function lines(array $cart, CurrencyService $currencies): array
    {
        $parsed = [];
        foreach ($cart as $key => $quantity) {
            [$productId, $variantId] = $this->parseKey((string) $key);
            $parsed[(string) $key] = ['product_id' => $productId, 'variant_id' => $variantId, 'quantity' => max(1, (int) $quantity)];
        }
        $productIds = array_values(array_unique(array_column($parsed, 'product_id')));
        $products = Product::with(['media', 'priceTiers', 'variants'])->whereIn('id', $productIds)->where('status', 'published')->get()->keyBy('id');
        $lines = [];
        foreach ($parsed as $key => $entry) {
            $product = $products->get($entry['product_id']);
            if (!$product) continue;
            $variant = $entry['variant_id'] ? $product->variants->firstWhere('id', $entry['variant_id']) : null;
            if ($entry['variant_id'] && !$variant) continue;
            $quantity = $entry['quantity'];
            $tier = null;
            if ($product->b2b_enabled) {
                $tier = $product->priceTiers->first(fn ($item) => $quantity >= $item->min_quantity && ($item->max_quantity === null || $quantity <= $item->max_quantity));
            }
            $sourceCurrency = $tier?->currency ?? $product->base_currency ?? 'USD';
            $sourcePrice = $tier ? (float) $tier->unit_price : (float) $product->retail_price;
            if ($variant) $sourcePrice += (float) $variant->price_adjustment;
            $converted = $currencies->convert($sourcePrice, $sourceCurrency, $currencies->currentCurrency());
            $lines[] = [
                'key' => $key, 'product' => $product, 'variant' => $variant, 'quantity' => $quantity,
                'stock' => $variant ? (int) $variant->stock : (int) $product->stock,
                'unit_price' => $converted['amount'], 'line_total' => round($converted['amount'] * $quantity, 2),
                'currency' => $converted['currency'], 'converted' => $converted['converted'],
            ];
        }
        return $lines;
    }

    public function parseKey(string $key): array
    {
        $parts = explode(':', $key, 2);
        $productId = filter_var($parts[0] ?? null, FILTER_VALIDATE_INT);
        $variantId = isset($parts[1]) ? filter_var($parts[1], FILTER_VALIDATE_INT) : 0;
        if (!$productId || $productId < 1 || ($variantId !== 0 && (!$variantId || $variantId < 1))) return [0, 0];
        return [(int) $productId, (int) $variantId];
    }
}
