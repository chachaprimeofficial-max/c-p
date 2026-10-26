<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class StorefrontController extends Controller
{
    public function home(): View
    {
        return view('storefront.home', [
            'featuredProducts' => Product::with(['media', 'priceTiers'])->where('status', 'published')->where('featured', true)->latest()->take(8)->get(),
            'latestProducts' => Product::with('media')->where('status', 'published')->latest()->take(12)->get(),
            'categories' => Category::whereNull('parent_id')->where('is_active', true)->orderBy('sort_order')->take(8)->get(),
        ]);
    }

    public function products(Request $request): View
    {
        $query = Product::with(['media', 'category', 'priceTiers'])->where('status', 'published');
        if ($request->filled('q')) {
            $term = trim((string) $request->query('q'));
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', '%'.$term.'%')->orWhere('brand_name', 'like', '%'.$term.'%')->orWhere('product_code', 'like', '%'.$term.'%');
            });
        }
        if ($request->filled('category')) {
            $query->where('category_id', $request->integer('category'));
        }
        if ($request->boolean('b2b')) $query->where('b2b_enabled', true);
        if ($request->boolean('group_buy')) $query->where('group_buy_enabled', true);

        return view('storefront.products', [
            'products' => $query->latest()->paginate(16)->withQueryString(),
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function show(Product $product): View
    {
        abort_unless($product->status === 'published', 404);
        $product->load(['media', 'category', 'priceTiers', 'variants', 'groupBuyCampaigns']);
        return view('storefront.show', compact('product'));
    }

    public function setLocale(string $locale): RedirectResponse
    {
        abort_unless(array_key_exists($locale, config('marketplace.locales')), 404);
        session(['locale' => $locale]);
        app()->setLocale($locale);
        return back();
    }

    public function setCurrency(string $currency): RedirectResponse
    {
        abort_unless(array_key_exists(strtoupper($currency), config('marketplace.currencies')), 404);
        session(['currency' => strtoupper($currency)]);
        return back();
    }
}
