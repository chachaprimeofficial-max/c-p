<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class CurrencyService
{
    public function currentCurrency(): string
    {
        $currency = strtoupper((string) session('currency', config('marketplace.default_currency', 'USD')));
        return array_key_exists($currency, config('marketplace.currencies', [])) ? $currency : 'USD';
    }

    public function convert(float $amount, string $from, ?string $to = null): array
    {
        $from = strtoupper($from ?: 'USD');
        $to = strtoupper($to ?: $this->currentCurrency());
        if ($from === $to) return ['amount' => $amount, 'currency' => $from, 'converted' => true];
        try {
            $payload = Cache::remember('cp_currency_rates_usd_v1', now()->addHours(12), function () {
                return Http::timeout(4)->retry(1, 150)->get('https://open.er-api.com/v6/latest/USD')->throw()->json();
            });
            $rates = $payload['rates'] ?? [];
            if (($payload['result'] ?? null) !== 'success' || !isset($rates[$from], $rates[$to]) || (float) $rates[$from] <= 0) {
                return ['amount' => $amount, 'currency' => $from, 'converted' => false];
            }
            return ['amount' => round($amount / (float) $rates[$from] * (float) $rates[$to], 2), 'currency' => $to, 'converted' => true];
        } catch (Throwable) {
            return ['amount' => $amount, 'currency' => $from, 'converted' => false];
        }
    }
}
