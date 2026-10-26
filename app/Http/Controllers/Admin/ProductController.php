<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPriceTier;
use App\Models\ShippingTemplate;
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
        return view('admin.products.create', ['categories' => Category::where('is_active', true)->orderBy('name')->get(), 'brands' => Brand::where('is_active', true)->orderBy('name')->get(), 'shippingTemplates' => ShippingTemplate::where('is_active', true)->orderBy('name')->get()]);
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
            'currency' => ['nullable', 'in:PKR,CNY,USD,EUR,GBP,JPY,AED,SAR,TRY'],
            'stock' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:draft,published,archived'],
            'selling_countries_text' => ['nullable', 'string', 'max:1000'],
            'sku' => ['nullable', 'string', 'max:80', 'unique:products,sku'],
            'upc' => ['nullable', 'string', 'max:32'],
            'gtin' => ['nullable', 'string', 'max:32'],
            'product_weight_lb' => ['nullable', 'numeric', 'min:0'],
            'package_weight_lb' => ['nullable', 'numeric', 'min:0'],
            'liters' => ['nullable', 'numeric', 'min:0'],
            'package_dimensions' => ['nullable', 'string', 'max:120'],
            'shipping_template_id' => ['nullable', 'integer', 'exists:shipping_templates,id'],
            'shipping_template' => ['nullable', 'string', 'max:150'],
            'handling_time_days' => ['nullable', 'integer', 'min:0', 'max:90'],
            'delivery_method' => ['nullable', 'in:courier,freight,pickup,digital'],
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'supplier_contact' => ['nullable', 'string', 'max:2000'],
            'courier_name' => ['nullable', 'string', 'max:150'],
            'tracking_numbers_text' => ['nullable', 'string', 'max:1000'],
            'return_address' => ['nullable', 'string', 'max:2000'],
            'return_policy' => ['nullable', 'string', 'max:10000'],
            'warranty' => ['nullable', 'string', 'max:2000'],
            'product_images' => ['nullable', 'array', 'max:12'],
            'product_images.*' => ['image', 'mimes:jpg,jpeg,png,webp,avif', 'max:5120'],
            'product_video' => ['nullable', 'file', 'mimes:mp4,webm,mov', 'max:51200'],
            'blueprints' => ['nullable', 'array', 'max:5'],
            'blueprints.*' => ['file', 'mimes:pdf,png,jpg,jpeg', 'max:10240'],
            'product_documents' => ['nullable', 'array', 'max:10'],
            'product_documents.*' => ['file', 'mimes:pdf,doc,docx,xls,xlsx', 'max:15360'],
            'b2b_enabled' => ['nullable', 'boolean'],
            'group_buy_enabled' => ['nullable', 'boolean'],
            'has_variations' => ['nullable', 'in:0,1'],
            'featured' => ['nullable', 'boolean'],
            'tier_min' => ['nullable', 'array', 'max:10'],
            'tier_min.*' => ['nullable', 'integer', 'min:1'],
            'tier_max' => ['nullable', 'array', 'max:10'],
            'tier_max.*' => ['nullable', 'integer', 'min:1'],
            'tier_price' => ['nullable', 'array', 'max:10'],
            'tier_price.*' => ['nullable', 'numeric', 'min:0'],
            'main_variation' => ['nullable', 'string', 'max:100'],
            'variation_values' => ['nullable', 'string', 'max:1000'],
            'attributes_text' => ['nullable', 'string', 'max:2000'],
            'applicable_parcel' => ['nullable', 'string', 'max:150'],
            'sub_category_name' => ['nullable', 'string', 'max:150'],
            'sub_sub_category_name' => ['nullable', 'string', 'max:150'],
        ]);

        $data['slug'] = Str::slug($data['name']).'-'.Str::lower(Str::random(5));
        $data['base_currency'] = $data['currency'] ?? 'USD';
        $data['shipping_template'] = !empty($data['shipping_template_id']) ? (ShippingTemplate::find($data['shipping_template_id'])?->name) : null;
        $data['selling_countries'] = collect(explode(',', (string) ($data['selling_countries_text'] ?? '')))->map(fn ($v) => strtoupper(trim($v)))->filter(fn ($v) => preg_match('/^[A-Z]{2}$/', $v))->unique()->values()->all();
        $data['tracking_numbers'] = collect(explode(',', (string) ($data['tracking_numbers_text'] ?? '')))->map(fn ($v) => trim($v))->filter()->values()->all();
        $data['b2b_enabled'] = $request->boolean('b2b_enabled');
        $data['group_buy_enabled'] = $request->boolean('group_buy_enabled');
        $data['has_variations'] = $request->input('has_variations', '1') !== '0';
        $data['featured'] = $request->boolean('featured');
        $data['handling_time_days'] = $data['handling_time_days'] ?? 2;
        $data['attributes'] = array_filter([
            'main_variation' => $data['main_variation'] ?? null,
            'variation_values' => $data['variation_values'] ?? null,
            'attributes' => $data['attributes_text'] ?? null,
            'applicable_parcel' => $data['applicable_parcel'] ?? null,
            'sub_category_name' => $data['sub_category_name'] ?? null,
            'sub_sub_category_name' => $data['sub_sub_category_name'] ?? null,
        ], fn ($value) => filled($value));
        unset($data['currency'], $data['selling_countries_text'], $data['tracking_numbers_text'], $data['main_variation'], $data['variation_values'], $data['attributes_text'], $data['applicable_parcel'], $data['sub_category_name'], $data['sub_sub_category_name'], $data['tier_min'], $data['tier_max'], $data['tier_price'], $data['blueprints'], $data['product_documents'], $data['product_images'], $data['product_video']);

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
            foreach (['blueprints' => 'blueprint', 'product_documents' => 'document'] as $input => $type) {
                foreach ($request->file($input, []) as $index => $file) {
                    $path = $file->store('products/'.$product->id.'/'.$type.'s', 'public');
                    $product->media()->create(['type' => $type, 'path' => $path, 'sort_order' => $index]);
                }
            }
            foreach ($request->input('tier_min', []) as $i => $min) {
                $price = $request->input('tier_price.'.$i);
                $max = $request->input('tier_max.'.$i);
                if (is_numeric($min) && (int) $min > 0 && is_numeric($price) && (float) $price >= 0) {
                    if (filled($max) && (!is_numeric($max) || (int) $max < (int) $min)) continue;
                    ProductPriceTier::create([
                        'product_id' => $product->id, 'min_quantity' => (int) $min,
                        'max_quantity' => filled($max) ? (int) $max : null,
                        'unit_price' => (float) $price, 'currency' => $product->base_currency,
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
        return view('admin.products.edit', ['product' => $product, 'categories' => Category::where('is_active', true)->orderBy('name')->get(), 'brands' => Brand::where('is_active', true)->orderBy('name')->get()]);
    }

    public function update(Request $request, Product $product): RedirectResponse
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
            'currency' => ['required', 'in:PKR,CNY,USD,EUR,GBP,JPY,AED,SAR,TRY'],
            'stock' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:draft,published,archived'],
            'selling_countries_text' => ['nullable', 'string', 'max:1000'],
            'sku' => ['required', 'string', 'max:80', 'unique:products,sku,'.$product->id],
            'upc' => ['nullable', 'string', 'max:32'],
            'gtin' => ['nullable', 'string', 'max:32'],
            'product_weight_lb' => ['nullable', 'numeric', 'min:0'],
            'package_weight_lb' => ['nullable', 'numeric', 'min:0'],
            'liters' => ['nullable', 'numeric', 'min:0'],
            'package_dimensions' => ['nullable', 'string', 'max:120'],
            'shipping_template' => ['nullable', 'string', 'max:150'],
            'handling_time_days' => ['nullable', 'integer', 'min:0', 'max:90'],
            'delivery_method' => ['nullable', 'in:courier,freight,pickup,digital'],
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'supplier_contact' => ['nullable', 'string', 'max:2000'],
            'tracking_numbers_text' => ['nullable', 'string', 'max:1000'],
            'courier_name' => ['nullable', 'string', 'max:150'],
            'return_address' => ['nullable', 'string', 'max:2000'],
            'return_policy' => ['nullable', 'string', 'max:10000'],
            'warranty' => ['nullable', 'string', 'max:2000'],
            'product_images' => ['nullable', 'array', 'max:12'],
            'product_images.*' => ['image', 'mimes:jpg,jpeg,png,webp,avif', 'max:5120'],
            'product_video' => ['nullable', 'file', 'mimes:mp4,webm,mov', 'max:51200'],
            'blueprints' => ['nullable', 'array', 'max:5'],
            'blueprints.*' => ['file', 'mimes:pdf,png,jpg,jpeg', 'max:10240'],
            'product_documents' => ['nullable', 'array', 'max:10'],
            'product_documents.*' => ['file', 'mimes:pdf,doc,docx,xls,xlsx', 'max:15360'],
            'b2b_enabled' => ['nullable', 'boolean'],
            'group_buy_enabled' => ['nullable', 'boolean'],
            'has_variations' => ['nullable', 'in:0,1'],
            'featured' => ['nullable', 'boolean'],
            'tier_min' => ['nullable', 'array', 'max:10'],
            'tier_min.*' => ['nullable', 'integer', 'min:1'],
            'tier_max' => ['nullable', 'array', 'max:10'],
            'tier_max.*' => ['nullable', 'integer', 'min:1'],
            'tier_price' => ['nullable', 'array', 'max:10'],
            'tier_price.*' => ['nullable', 'numeric', 'min:0'],
            'main_variation' => ['nullable', 'string', 'max:100'],
            'variation_values' => ['nullable', 'string', 'max:1000'],
            'attributes_text' => ['nullable', 'string', 'max:2000'],
            'applicable_parcel' => ['nullable', 'string', 'max:150'],
            'sub_category_name' => ['nullable', 'string', 'max:150'],
            'sub_sub_category_name' => ['nullable', 'string', 'max:150'],
        ]);

        $data['slug'] = Str::slug($data['name']).'-'.Str::lower(Str::random(5));
        $data['base_currency'] = $data['currency'];
        $data['selling_countries'] = collect(explode(',', (string) ($data['selling_countries_text'] ?? '')))->map(fn ($v) => strtoupper(trim($v)))->filter(fn ($v) => preg_match('/^[A-Z]{2}$/', $v))->unique()->values()->all();
        $data['tracking_numbers'] = collect(explode(',', (string) ($data['tracking_numbers_text'] ?? '')))->map(fn ($v) => trim($v))->filter()->values()->all();
        $data['b2b_enabled'] = $request->boolean('b2b_enabled');
        $data['group_buy_enabled'] = $request->boolean('group_buy_enabled');
        $data['has_variations'] = $request->input('has_variations', '1') !== '0';
        $data['featured'] = $request->boolean('featured');
        $data['handling_time_days'] = $data['handling_time_days'] ?? 2;
        $data['attributes'] = array_filter([
            'main_variation' => $data['main_variation'] ?? null,
            'variation_values' => $data['variation_values'] ?? null,
            'attributes' => $data['attributes_text'] ?? null,
            'applicable_parcel' => $data['applicable_parcel'] ?? null,
            'sub_category_name' => $data['sub_category_name'] ?? null,
            'sub_sub_category_name' => $data['sub_sub_category_name'] ?? null,
        ], fn ($value) => filled($value));
        unset($data['currency'], $data['selling_countries_text'], $data['tracking_numbers_text'], $data['main_variation'], $data['variation_values'], $data['attributes_text'], $data['applicable_parcel'], $data['sub_category_name'], $data['sub_sub_category_name'], $data['tier_min'], $data['tier_max'], $data['tier_price'], $data['blueprints'], $data['product_documents'], $data['product_images'], $data['product_video']);

        DB::transaction(function () use ($data, $request, $product): void {
            $product->update($data);
            foreach ($request->file('product_images', []) as $index => $image) {
                $path = $image->store('products/'.$product->id, 'public');
                $product->media()->create(['type' => 'image', 'path' => $path, 'sort_order' => (int) ($product->media()->where('type', 'image')->max('sort_order') ?? -1) + $index + 1]);
            }
            if ($request->hasFile('product_video')) {
                $path = $request->file('product_video')->store('products/'.$product->id.'/video', 'public');
                $product->media()->create(['type' => 'video', 'path' => $path, 'sort_order' => 99]);
            }
            foreach (['blueprints' => 'blueprint', 'product_documents' => 'document'] as $input => $type) {
                foreach ($request->file($input, []) as $index => $file) {
                    $path = $file->store('products/'.$product->id.'/'.$type.'s', 'public');
                    $product->media()->create(['type' => $type, 'path' => $path, 'sort_order' => $index]);
                }
            }
            if ($request->exists('tier_min')) {
                $product->priceTiers()->delete();
                foreach ($request->input('tier_min', []) as $i => $min) {
                    $price = $request->input('tier_price.'.$i);
                    $max = $request->input('tier_max.'.$i);
                    if (is_numeric($min) && (int) $min > 0 && is_numeric($price) && (float) $price >= 0) {
                        if (filled($max) && (!is_numeric($max) || (int) $max < (int) $min)) continue;
                        ProductPriceTier::create([
                            'product_id' => $product->id, 'min_quantity' => (int) $min,
                            'max_quantity' => filled($max) ? (int) $max : null,
                            'unit_price' => (float) $price, 'currency' => $product->base_currency,
                            'label' => 'Quantity tier '.((int) $i + 1),
                        ]);
                    }
                }
            }
        });

        return redirect()->route('admin.products.index')->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();
        return redirect()->route('admin.products.index')->with('success', 'Product moved to trash.');
    }
}
