<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CurrencyService;
use Illuminate\View\View;

class SystemController extends Controller
{
    public function localization(CurrencyService $currencies): View
    {
        $currencyRows = [];
        foreach (config('marketplace.currencies') as $code => $currency) {
            $rate = $currencies->convert(1, 'USD', $code);
            $currencyRows[] = ['code' => $code, 'name' => $currency['name'], 'symbol' => $currency['symbol'], 'rate' => $rate['amount'], 'available' => $rate['converted']];
        }
        return view('admin.system.localization', ['locales' => config('marketplace.locales'), 'currencyRows' => $currencyRows]);
    }

    public function ai(): View
    {
        return view('admin.system.ai', [
            'configured' => filled(config('services.gemini.key')),
            'model' => config('services.gemini.model'),
        ]);
    }

    public function settings(): View
    {
        return view('admin.system.settings', [
            'appUrl' => config('app.url'),
            'appEnvironment' => config('app.env'),
            'debugEnabled' => config('app.debug'),
            'databaseDriver' => config('database.default'),
            'mailHost' => config('mail.mailers.smtp.host'),
            'mailUsername' => config('mail.mailers.smtp.username'),
            'mailConfigured' => filled(config('mail.mailers.smtp.host')) && filled(config('mail.mailers.smtp.username')) && filled(config('mail.mailers.smtp.password')),
            'stripeConfigured' => filled(config('services.stripe.secret')) && filled(config('services.stripe.webhook_secret')),
            'geminiConfigured' => filled(config('services.gemini.key')),
        ]);
    }
}
