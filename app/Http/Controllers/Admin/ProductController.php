<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductPriceTier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $query = Product::with('category')->latest();
        if ($request->filled('q')) {
            $term = trim((string) $request->query('q'));
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$term.'%')->orWhere('sku', 'like', '%'.$term.'%')->orWhere('product_code', 'like', '%'.$term.'%'));
        }
        if ($request->filled('status')) $query->where('status', $request->query('status'));
        return view('admin.products.index', ['products' => $query->paginate(15)->withQueryString()]);
    }

    public function create(): View
    {
        return view('admin.products.create', ['categories' => Category::where('is_active', true)->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:30000'],
            'specification' => ['nullable', 'string', 'max:20000'],
            'brand_name' => ['nullable', 'string', 'max:150'],
            'country_of_origin' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'list_price' => ['nullable', 'numeric', 'min:0'],
            'retail_price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:draft,published,archived'],
            'selling_countries' => ['nullable', 'array'],
            'selling_countries.*' => ['string', 'max:2'],
            'product_images' => ['nullable', 'array', 'max:12'],
            'product_images.*' => ['image', 'mimes:jpg,jpeg,png,webp,avif', 'max:5120'],
            'product_video' => ['nullable', 'mimes:mp4,webm,mov', 'max:51200'],
            'b2b_enabled' => ['nullable', 'boolean'],
            'group_buy_enabled' => ['nullable', 'boolean'],
            'has_variations' => ['nullable', 'boolean'],
            'handling_time_days' => ['nullable', 'integer', 'min:0', 'max:90'],
        ]);

        $data['slug'] = Str::slug($data['name']).'-'.Str::lower(Str::random(5));
        $data['selling_countries'] = $data['selling_countries'] ?? [];
        $data['b2b_enabled'] = $request->boolean('b2b_enabled');
        $data['group_buy_enabled'] = $request->boolean('group_buy_enabled');
        $data['has_variations'] = $request->boolean('has_variations', true);
        $data['handling_time_days'] = $data['handling_time_days'] ?? 2;
        $data['attributes'] = [];
        $data['featured'] = $request->boolean('featured');

        DB::transaction(function () use ($data, $request): void {
            $product = Product::create($data);
            foreach ($request->file('product_images', []) as $index => $image) {
                $path = $image->store('products/'.$product->id, 'public');
                $product->media()->create(['type' => 'image', 'path' => $path, 'sort_order' => $index]);
            }
            if ($request->hasFile('product_video')) {
                $path = $request->file('product_video')->store('products/'.$product->id.'/video', 'public');
                $product->media()->create(['type' => 'video', 'path' => $path, 'sort_order' => 99]);
            }
            foreach ($request->input('tier_min', []) as $i => $min) {
                $price = $request->input('tier_price.'.$i);
                if (is_numeric($min) && (int) $min > 0 && is_numeric($price) && (float) $price >= 0) {
                    ProductPriceTier::create([
                        'product_id' => $product->id, 'min_quantity' => (int) $min,
                        'max_quantity' => filled($request->input('tier_max.'.$i)) ? (int) $request->input('tier_max.'.$i) : null,
                        'unit_price' => (float) $price, 'currency' => strtoupper($request->input('currency', 'USD')),
                        'label' => 'Quantity tier '.((int) $i + 1),
                    ]);
                }
            }
        });

        return redirect()->route('admin.products.index')->with('success', 'Product saved successfully.');
    }

    public function edit(Product $product): View
    {
        $product->load(['media', 'priceTiers']);
        return view('admin.products.edit', ['product' => $product, 'categories' => Category::where('is_active', true)->orderBy('name')->get()]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:30000'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'list_price' => ['nullable', 'numeric', 'min:0'],
            'retail_price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:draft,published,archived'],
        ]);
        $product->update($data);
        return redirect()->route('admin.products.index')->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();
        return redirect()->route('admin.products.index')->with('success', 'Product deleted.');
    }
}
