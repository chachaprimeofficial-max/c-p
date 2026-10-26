<?php

namespace App\Services;

use RuntimeException;

class ShippingCalculatorService
{
    public function calculate(array $lines, string $country, string $currency, CurrencyService $currencies): float
    {
        $country = strtoupper($country);
        $total = 0.0;
        foreach ($lines as $line) {
            $product = $line['product'];
            $quantity = (int) $line['quantity'];
            $template = $product->shippingTemplate;
            if (!$template || !$template->is_active) throw new RuntimeException('Shipping is not configured for '.$product->name.'. Ask the administrator to assign an active shipping template before checkout.');
            $countries = array_map('strtoupper', $template->country_codes ?? []);
            if (!in_array('*', $countries, true) && !in_array($country, $countries, true)) {
                throw new RuntimeException('No shipping template for '.$product->name.' delivers to '.$country.'. Please contact support for a shipping quote.');
            }
            $weight = (float) ($product->package_weight_lb ?? $product->product_weight_lb ?? 0);
            $sourceTotal = ((float) $template->base_rate + ((float) $template->per_lb_rate * $weight)) * $quantity;
            $converted = $currencies->convert($sourceTotal, $template->currency, $currency);
            if (!$converted['converted'] || $converted['currency'] !== $currency) throw new RuntimeException('The shipping currency conversion is unavailable. Please try again later.');
            $total += (float) $converted['amount'];
        }
        return round($total, 2);
    }
}
