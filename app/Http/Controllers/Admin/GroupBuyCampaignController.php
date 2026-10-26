<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GroupBuyCampaign;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GroupBuyCampaignController extends Controller
{
    public function index(): View
    {
        return view('admin.group-buy.index', ['campaigns' => GroupBuyCampaign::with('product')->latest()->paginate(20)]);
    }

    public function create(): View
    {
        return view('admin.group-buy.create', ['products' => Product::where('status', 'published')->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'title' => ['required', 'string', 'max:200'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'in:PKR,CNY,USD,EUR,GBP,JPY,AED,SAR,TRY'],
            'target_participants' => ['required', 'integer', 'min:2', 'max:1000000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'status' => ['required', 'in:draft,active,completed,cancelled'],
        ]);
        Product::whereKey($data['product_id'])->update(['group_buy_enabled' => true]);
        GroupBuyCampaign::create($data + ['current_participants' => 0]);
        return redirect()->route('admin.group-buy.index')->with('success', 'Group-buy campaign created.');
    }

    public function edit(GroupBuyCampaign $groupBuyCampaign): View
    {
        return view('admin.group-buy.edit', ['campaign' => $groupBuyCampaign, 'products' => Product::where('status', 'published')->orderBy('name')->get()]);
    }

    public function update(Request $request, GroupBuyCampaign $groupBuyCampaign): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'in:PKR,CNY,USD,EUR,GBP,JPY,AED,SAR,TRY'],
            'target_participants' => ['required', 'integer', 'min:2', 'max:1000000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'status' => ['required', 'in:draft,active,completed,cancelled'],
        ]);
        $groupBuyCampaign->update($data);
        return redirect()->route('admin.group-buy.index')->with('success', 'Group-buy campaign updated.');
    }

    public function destroy(GroupBuyCampaign $groupBuyCampaign): RedirectResponse
    {
        $groupBuyCampaign->delete();
        return redirect()->route('admin.group-buy.index')->with('success', 'Group-buy campaign deleted.');
    }
}
