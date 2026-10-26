<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class ProductVariantController extends Controller
{
    public function index(Product $product): View
    {
        $product->load('variants');
        return view('admin.products.variants', compact('product'));
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'sku' => ['required', 'string', 'max:100', 'unique:product_variants,sku'],
            'stock' => ['required', 'integer', 'min:0'],
            'price_adjustment' => ['required', 'numeric', 'min:-999999', 'max:999999'],
            'barcode' => ['nullable', 'string', 'max:150'],
            'attributes_text' => ['nullable', 'string', 'max:2000'],
        ]);
        ProductVariant::create([
            'product_id' => $product->id,
            'name' => trim($data['name']),
            'sku' => trim($data['sku']),
            'stock' => (int) $data['stock'],
            'price_adjustment' => (float) $data['price_adjustment'],
            'barcode' => $data['barcode'] ?? null,
            'attributes' => $this->parseAttributes($data['attributes_text'] ?? ''),
        ]);
        $product->update(['has_variations' => true, 'stock' => $product->variants()->sum('stock')]);
        return back()->with('success', 'Product variation added.');
    }

    public function update(Request $request, Product $product, ProductVariant $variant): RedirectResponse
    {
        abort_unless($variant->product_id === $product->id, 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'sku' => ['required', 'string', 'max:100', 'unique:product_variants,sku,'.$variant->id],
            'stock' => ['required', 'integer', 'min:0'],
            'price_adjustment' => ['required', 'numeric', 'min:-999999', 'max:999999'],
            'barcode' => ['nullable', 'string', 'max:150'],
            'attributes_text' => ['nullable', 'string', 'max:2000'],
        ]);
        $variant->update([
            'name' => trim($data['name']),
            'sku' => trim($data['sku']),
            'stock' => (int) $data['stock'],
            'price_adjustment' => (float) $data['price_adjustment'],
            'barcode' => $data['barcode'] ?? null,
            'attributes' => $this->parseAttributes($data['attributes_text'] ?? ''),
        ]);
        $product->update(['stock' => $product->variants()->sum('stock')]);
        return back()->with('success', 'Product variation updated.');
    }

    public function destroy(Product $product, ProductVariant $variant): RedirectResponse
    {
        abort_unless($variant->product_id === $product->id, 404);
        $variant->delete();
        $product->update(['stock' => $product->variants()->sum('stock')]);
        return back()->with('success', 'Product variation deleted.');
    }

    private function parseAttributes(string $input): array
    {
        $attributes = [];
        foreach (explode(',', $input) as $pair) {
            $parts = explode(':', $pair, 2);
            if (count($parts) !== 2) continue;
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            if ($key !== '' && $value !== '') $attributes[mb_substr($key, 0, 80)] = mb_substr($value, 0, 150);
        }
        return $attributes;
    }
}
