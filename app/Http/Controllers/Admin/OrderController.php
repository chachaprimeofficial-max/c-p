<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderEmailService;
use Throwable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $query = Order::withCount('items')->latest();
        if ($request->filled('q')) {
            $term = trim((string) $request->query('q'));
            $query->where(fn ($q) => $q->where('order_number', 'like', '%'.$term.'%')->orWhere('email', 'like', '%'.$term.'%')->orWhere('customer_name', 'like', '%'.$term.'%'));
        }
        if ($request->filled('status')) $query->where('order_status', $request->query('status'));
        if ($request->filled('payment')) $query->where('payment_status', $request->query('payment'));
        return view('admin.orders.index', ['orders' => $query->paginate(20)->withQueryString()]);
    }

    public function show(Order $order): View
    {
        $order->load(['items.product', 'user', 'returns']);
        return view('admin.orders.show', compact('order'));
    }

    public function update(Request $request, Order $order, OrderEmailService $emails): RedirectResponse
    {
        $data = $request->validate([
            'order_status' => ['required', 'in:pending_payment,processing,shipped,delivered,cancelled,manual_review'],
            'courier_name' => ['nullable', 'string', 'max:150'],
            'tracking_number' => ['nullable', 'string', 'max:150'],
            'customer_notes' => ['nullable', 'string', 'max:2000'],
        ]);
        if (in_array($data['order_status'], ['processing', 'shipped', 'delivered'], true) && $order->payment_status !== 'paid') {
            return back()->withErrors(['order_status' => 'A payment must be verified before this order can be processed or shipped.']);
        }
        $wasShipped = $order->order_status === 'shipped';
        if ($data['order_status'] === 'shipped' && !$order->shipped_at) $data['shipped_at'] = now();
        $order->update($data);
        if ($data['order_status'] === 'shipped' && !$wasShipped) {
            try { $emails->shipmentUpdated($order->fresh()); }
            catch (Throwable $exception) { report($exception); }
        }
        return redirect()->route('admin.orders.show', $order)->with('success', 'Order updated.');
    }
}
