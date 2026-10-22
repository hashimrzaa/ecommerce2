<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ReturnRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $orders = Order::withCount('items')
            ->when($request->query('status'), fn ($w, $s) => $w->where('status', $s))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('number', 'like', "%$q%")->orWhere('name', 'like', "%$q%")->orWhere('phone', 'like', '%'.(normalize_phone($q) ?: $q).'%')))
            ->latest()->paginate(20)->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'counts' => Order::selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status'),
        ]);
    }

    public function show(Order $order)
    {
        return view('admin.orders.show', ['order' => $order->load(['items.product', 'events', 'returns', 'user'])]);
    }

    public function update(Request $request, Order $order)
    {
        $data = $request->validate([
            'status' => ['nullable', Rule::in($order->nextStatuses())],
            'confirmed_via' => ['nullable', Rule::in(['phone', 'whatsapp'])],
            'courier' => ['nullable', 'string', 'max:60'],
            'tracking_no' => ['nullable', 'string', 'max:60'],
            'note' => ['nullable', 'string', 'max:300'],
        ]);

        $order->update(array_filter([
            'courier' => $data['courier'] ?? null,
            'tracking_no' => $data['tracking_no'] ?? null,
            'confirmed_via' => $data['confirmed_via'] ?? null,
        ]));

        if (! empty($data['status'])) {
            if ($data['status'] === 'confirmed' && ! $order->confirmed_via) {
                return back()->withErrors(['confirmed_via' => 'Select how the customer confirmed this order (phone or WhatsApp).']);
            }
            $order->transitionTo($data['status'], $data['note'] ?? null, $request->user()->name);
            if ($data['status'] === 'returned') {
                ReturnRequest::firstOrCreate(['order_id' => $order->id, 'type' => 'rto'], ['reason' => $data['note'] ?? 'Parcel returned to origin', 'status' => 'requested']);
            }

            return back()->with('toast', "Order {$order->number} marked as {$order->status_label}.");
        }

        return back()->with('toast', 'Order details saved.');
    }
}
