<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderReturn;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerOrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = Order::withCount('items')->where('user_id', $request->user()->id)->latest()->paginate(10);
        return view('account.orders.index', compact('orders'));
    }

    public function show(Request $request, Order $order): View
    {
        abort_unless($order->user_id === $request->user()->id, 404);
        $order->load(['items', 'returns']);
        return view('account.orders.show', compact('order'));
    }

    public function requestReturn(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);
        if ($order->payment_status !== 'paid' || $order->order_status !== 'delivered') {
            return back()->withErrors(['return' => 'Returns can be requested after an order is marked delivered and paid.']);
        }
        if ($order->returns()->whereIn('status', ['requested', 'reviewing', 'approved'])->exists()) {
            return back()->withErrors(['return' => 'A return request is already open for this order.']);
        }
        $data = $request->validate([
            'reason' => ['required', 'in:damaged,wrong_item,not_as_described,changed_mind,other'],
            'details' => ['nullable', 'string', 'max:3000'],
        ]);
        OrderReturn::create(['order_id' => $order->id, 'user_id' => $request->user()->id, 'reason' => $data['reason'], 'details' => $data['details'] ?? null, 'status' => 'requested']);
        return redirect()->route('account.orders.show', $order)->with('success', 'Your return request has been submitted for review.');
    }
}
