<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ShippingTemplateController extends Controller
{
    public function index(): View
    {
        return view('admin.shipping.index', ['templates' => ShippingTemplate::withCount('products')->orderBy('name')->paginate(20)]);
    }

    public function create(): View
    {
        return view('admin.shipping.form', ['template' => new ShippingTemplate(), 'editing' => false]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = Str::slug($data['name']).'-'.Str::lower(Str::random(4));
        $data['country_codes'] = $this->countryCodes($data['country_codes_text']);
        $data['is_active'] = $request->boolean('is_active');
        unset($data['country_codes_text']);
        ShippingTemplate::create($data);
        return redirect()->route('admin.shipping.index')->with('success', 'Shipping template created.');
    }

    public function edit(ShippingTemplate $shipping): View
    {
        return view('admin.shipping.form', ['template' => $shipping, 'editing' => true]);
    }

    public function update(Request $request, ShippingTemplate $shipping): RedirectResponse
    {
        $data = $this->validated($request, $shipping);
        $data['slug'] = Str::slug($data['name']).'-'.Str::lower(Str::random(4));
        $data['country_codes'] = $this->countryCodes($data['country_codes_text']);
        $data['is_active'] = $request->boolean('is_active');
        unset($data['country_codes_text']);
        $shipping->update($data);
        return redirect()->route('admin.shipping.index')->with('success', 'Shipping template updated.');
    }

    public function destroy(ShippingTemplate $shipping): RedirectResponse
    {
        if ($shipping->products()->exists()) return back()->withErrors(['shipping' => 'This template is assigned to products. Reassign those products before deleting it.']);
        $shipping->delete();
        return redirect()->route('admin.shipping.index')->with('success', 'Shipping template deleted.');
    }

    private function validated(Request $request, ?ShippingTemplate $shipping = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150', 'unique:shipping_templates,name'.($shipping ? ','.$shipping->id : '')],
            'country_codes_text' => ['required', 'string', 'max:1000'],
            'currency' => ['required', 'in:PKR,CNY,USD,EUR,GBP,JPY,AED,SAR,TRY'],
            'base_rate' => ['required', 'numeric', 'min:0'],
            'per_lb_rate' => ['required', 'numeric', 'min:0'],
            'handling_days' => ['required', 'integer', 'min:0', 'max:90'],
            'delivery_method' => ['required', 'in:courier,freight,pickup'],
        ]);
    }

    private function countryCodes(string $text): array
    {
        $codes = collect(explode(',', $text))->map(fn ($code) => strtoupper(trim($code)))->filter(fn ($code) => $code === '*' || preg_match('/^[A-Z]{2}$/', $code))->unique()->values()->all();
        if (!$codes) throw \Illuminate\Validation\ValidationException::withMessages(['country_codes_text' => 'Enter two-letter country codes separated by commas, or use * for all countries.']);
        return $codes;
    }
}
