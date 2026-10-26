<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderReturn;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReturnController extends Controller
{
    public function index(Request $request): View
    {
        $query = OrderReturn::with(['order', 'user'])->latest();
        if ($request->filled('status')) $query->where('status', $request->query('status'));
        if ($request->filled('q')) {
            $term = trim((string) $request->query('q'));
            $query->whereHas('order', fn ($q) => $q->where('order_number', 'like', '%'.$term.'%'));
        }
        return view('admin.returns.index', ['returns' => $query->paginate(20)->withQueryString()]);
    }

    public function update(Request $request, OrderReturn $orderReturn): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:requested,reviewing,approved,rejected,refunded,closed'],
            'admin_notes' => ['nullable', 'string', 'max:3000'],
        ]);
        $orderReturn->update($data);
        return redirect()->route('admin.returns.index')->with('success', 'Return request updated.');
    }
}
