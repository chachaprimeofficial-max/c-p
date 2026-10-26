<?php

namespace App\Services;

use App\Models\Product;
use App\Models\GroupBuyCampaign;

class CartPricingService
{
    public function lines(array $cart, CurrencyService $currencies): array
    {
        $parsed = [];
        foreach ($cart as $key => $quantity) {
            [$productId, $variantId, $campaignId] = $this->parseKey((string) $key);
            $parsed[(string) $key] = [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'campaign_id' => $campaignId,
                'quantity' => max(1, (int) $quantity),
            ];
        }

        $productIds = array_values(array_unique(array_column($parsed, 'product_id')));
        $products = Product::with(['media', 'priceTiers', 'variants', 'groupBuyCampaigns'])
            ->whereIn('id', $productIds)->where('status', 'published')->get()->keyBy('id');
        $lines = [];

        foreach ($parsed as $key => $entry) {
            $product = $products->get($entry['product_id']);
            if (!$product) continue;

            $variant = $entry['variant_id'] ? $product->variants->firstWhere('id', $entry['variant_id']) : null;
            if ($entry['variant_id'] && !$variant) continue;

            $campaign = null;
            if ($entry['campaign_id']) {
                $campaign = $product->groupBuyCampaigns->firstWhere('id', $entry['campaign_id']);
                if (!$campaign || !$this->campaignIsAvailable($campaign)) continue;
            }

            $quantity = $entry['quantity'];
            $tier = null;
            if (!$campaign && $product->b2b_enabled) {
                $tier = $product->priceTiers->first(fn ($item) => $quantity >= $item->min_quantity && ($item->max_quantity === null || $quantity <= $item->max_quantity));
            }

            $sourceCurrency = $campaign?->currency ?? $tier?->currency ?? $product->base_currency ?? 'USD';
            $sourcePrice = $campaign ? (float) $campaign->unit_price : ($tier ? (float) $tier->unit_price : (float) $product->retail_price);
            if ($variant) $sourcePrice += (float) $variant->price_adjustment;
            $converted = $currencies->convert($sourcePrice, $sourceCurrency, $currencies->currentCurrency());

            $lines[] = [
                'key' => $key, 'product' => $product, 'variant' => $variant, 'campaign' => $campaign,
                'quantity' => $quantity, 'stock' => $variant ? (int) $variant->stock : (int) $product->stock,
                'unit_price' => $converted['amount'], 'line_total' => round($converted['amount'] * $quantity, 2),
                'currency' => $converted['currency'], 'converted' => $converted['converted'],
            ];
        }

        return $lines;
    }

    public function parseKey(string $key): array
    {
        $parts = explode(':', $key);
        $productId = filter_var($parts[0] ?? null, FILTER_VALIDATE_INT);
        $variantId = isset($parts[1]) ? filter_var($parts[1], FILTER_VALIDATE_INT) : 0;
        $campaignId = isset($parts[2]) ? filter_var($parts[2], FILTER_VALIDATE_INT) : 0;
        if (!$productId || $productId < 1
            || ($variantId !== 0 && (!$variantId || $variantId < 1))
            || ($campaignId !== 0 && (!$campaignId || $campaignId < 1))
            || count($parts) > 3) {
            return [0, 0, 0];
        }
        return [(int) $productId, (int) $variantId, (int) $campaignId];
    }

    private function campaignIsAvailable(GroupBuyCampaign $campaign): bool
    {
        return $campaign->status === 'active'
            && (!$campaign->starts_at || $campaign->starts_at->isPast())
            && (!$campaign->ends_at || $campaign->ends_at->isFuture())
            && $campaign->current_participants < $campaign->target_participants;
    }
}
