<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function index(Request $request): View
    {
        $query = Brand::query()->orderBy('name');
        if ($request->filled('q')) $query->where('name', 'like', '%'.trim((string) $request->query('q')).'%');
        return view('admin.brands.index', ['brands' => $query->paginate(25)->withQueryString()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:150', 'unique:brands,name'], 'description' => ['nullable', 'string', 'max:2000']]);
        $data['slug'] = Str::slug($data['name']).'-'.Str::lower(Str::random(4));
        $data['is_active'] = $request->boolean('is_active', true);
        Brand::create($data);
        return redirect()->route('admin.brands.index')->with('success', 'Brand created.');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        $brand->delete();
        return redirect()->route('admin.brands.index')->with('success', 'Brand deleted.');
    }
}
