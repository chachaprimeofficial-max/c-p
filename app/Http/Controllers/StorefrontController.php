<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    public function home(CurrencyService $currencies): View
    {
        $featuredProducts = Product::with(['media', 'priceTiers', 'category'])
            ->where('status', 'published')->where('featured', true)->latest()->take(8)->get();
        $featuredProducts->each(fn (Product $product) => $this->decorateProduct($product, $currencies));

        return view('storefront.home', [
            'featuredProducts' => $featuredProducts,
            'latestProducts' => Product::with('media')->where('status', 'published')->latest()->take(12)->get(),
            'categories' => Category::whereNull('parent_id')->where('is_active', true)->orderBy('sort_order')->take(8)->get(),
        ]);
    }

    public function products(Request $request, CurrencyService $currencies): View
    {
        $query = Product::with(['media', 'category', 'priceTiers'])->where('status', 'published');
        if ($request->filled('q')) {
            $term = trim((string) $request->query('q'));
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', '%'.$term.'%')->orWhere('brand_name', 'like', '%'.$term.'%')->orWhere('product_code', 'like', '%'.$term.'%');
            });
        }
        if ($request->filled('category')) $query->where('category_id', $request->integer('category'));
        if ($request->boolean('b2b')) $query->where('b2b_enabled', true);
        if ($request->boolean('group_buy')) $query->where('group_buy_enabled', true);

        $products = $query->latest()->paginate(16)->withQueryString();
        $products->getCollection()->each(fn (Product $product) => $this->decorateProduct($product, $currencies));

        return view('storefront.products', [
            'products' => $products,
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function show(Product $product, CurrencyService $currencies): View
    {
        abort_unless($product->status === 'published', 404);
        $product->load(['media', 'category', 'priceTiers', 'variants', 'groupBuyCampaigns']);
        $this->decorateProduct($product, $currencies);
        foreach ($product->priceTiers as $tier) {
            $converted = $currencies->convert((float) $tier->unit_price, $tier->currency, $currencies->currentCurrency());
            $tier->setAttribute('display_price', $converted['amount']);
            $tier->setAttribute('display_currency', $converted['currency']);
            $tier->setAttribute('currency_converted', $converted['converted']);
        }
        return view('storefront.show', compact('product'));
    }

    private function decorateProduct(Product $product, CurrencyService $currencies): void
    {
        $converted = $currencies->convert((float) $product->retail_price, $product->base_currency ?: 'USD', $currencies->currentCurrency());
        $product->setAttribute('display_price', $converted['amount']);
        $product->setAttribute('display_currency', $converted['currency']);
        $product->setAttribute('currency_converted', $converted['converted']);
        foreach ($product->priceTiers as $tier) {
            $tierConverted = $currencies->convert((float) $tier->unit_price, $tier->currency, $currencies->currentCurrency());
            $tier->setAttribute('display_price', $tierConverted['amount']);
            $tier->setAttribute('display_currency', $tierConverted['currency']);
            $tier->setAttribute('currency_converted', $tierConverted['converted']);
        }
    }

    public function setLocale(string $locale): RedirectResponse
    {
        abort_unless(array_key_exists($locale, config('marketplace.locales')), 404);
        session(['locale' => $locale]);
        return back();
    }

    public function setCurrency(string $currency): RedirectResponse
    {
        abort_unless(array_key_exists(strtoupper($currency), config('marketplace.currencies')), 404);
        session(['currency' => strtoupper($currency)]);
        return back();
    }
}
