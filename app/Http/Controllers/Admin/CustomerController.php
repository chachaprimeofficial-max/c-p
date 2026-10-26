<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::where('is_admin', false)->withCount('orders')->latest();
        if ($request->filled('q')) {
            $term = trim((string) $request->query('q'));
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$term.'%')->orWhere('email', 'like', '%'.$term.'%'));
        }
        return view('admin.customers.index', ['customers' => $query->paginate(20)->withQueryString()]);
    }

    public function show(User $customer): View
    {
        abort_if($customer->is_admin, 404);
        $customer->load(['orders' => fn ($query) => $query->latest()->take(20)]);
        return view('admin.customers.show', compact('customer'));
    }
}
