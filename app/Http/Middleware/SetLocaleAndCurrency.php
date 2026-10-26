<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleAndCurrency
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = (string) $request->session()->get('locale', config('app.locale', 'en'));
        if (!array_key_exists($locale, config('marketplace.locales', []))) $locale = 'en';
        app()->setLocale($locale);

        $currency = strtoupper((string) $request->session()->get('currency', config('marketplace.default_currency', 'USD')));
        if (!array_key_exists($currency, config('marketplace.currencies', []))) $currency = 'USD';
        $request->session()->put('currency', $currency);

        return $next($request);
    }
}
