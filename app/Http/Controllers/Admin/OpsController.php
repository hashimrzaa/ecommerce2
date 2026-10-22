<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ReturnRequest;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** Customers, returns/RTO, reports and staff permissions. */
class OpsController extends Controller
{
    /* ---------- Customers ---------- */

    public function customers(Request $request)
    {
        $q = trim((string) $request->query('q'));
        // COD shoppers mostly check out as guests, so a customer is identified by phone number.
        $customers = Order::query()
            ->selectRaw("phone, MAX(name) as name, MAX(city) as city, COUNT(*) as orders_count, MAX(created_at) as last_order_at,
                SUM(CASE WHEN status NOT IN ('cancelled','returned') THEN total ELSE 0 END) as spent,
                SUM(CASE WHEN status = 'returned' THEN 1 ELSE 0 END) as rto_count")
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('name', 'like', "%$q%")->orWhere('phone', 'like', '%'.(normalize_phone($q) ?: $q).'%')->orWhere('city', 'like', "%$q%")))
            ->groupBy('phone')->orderByDesc('spent')->paginate(25)->withQueryString();

        return view('admin.customers', ['customers' => $customers, 'registered' => User::where('role', 'customer')->count()]);
    }

    /* ---------- Returns / RTO ---------- */

    public function returns(Request $request)
    {
        return view('admin.returns', [
            'returns' => ReturnRequest::with('order')->when($request->query('type'), fn ($w, $t) => $w->where('type', $t))
                ->orderByRaw("status = 'requested' DESC")->latest()->paginate(20)->withQueryString(),
            'summary' => ReturnRequest::selectRaw('type, COUNT(*) c')->groupBy('type')->pluck('c', 'type'),
        ]);
    }

    public function storeReturn(Request $request)
    {
        $data = $request->validate([
            'order_number' => ['required', 'exists:orders,number'],
            'type' => ['required', Rule::in(['return', 'exchange', 'rto'])],
            'reason' => ['required', 'string', 'max:300'],
            'amount' => ['nullable', 'integer', 'min:0'],
        ], ['order_number.exists' => 'No order found with that number.']);
        $order = Order::where('number', $data['order_number'])->first();
        ReturnRequest::create(['order_id' => $order->id, 'type' => $data['type'], 'reason' => $data['reason'], 'amount' => (int) ($data['amount'] ?? 0)]);

        return back()->with('toast', 'Return request logged.');
    }

    public function updateReturn(Request $request, ReturnRequest $return)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['requested', 'approved', 'received', 'refunded', 'rejected'])],
            'notes' => ['nullable', 'string', 'max:300'],
        ]);
        $return->update($data);

        return back()->with('toast', 'Return request updated.');
    }

    /* ---------- Reports ---------- */

    public function reports(Request $request)
    {
        $days = in_array($request->integer('days'), [7, 30, 90], true) ? $request->integer('days') : 30;
        $since = now()->subDays($days)->startOfDay();
        $valid = fn () => OrderItem::join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.created_at', '>=', $since)->whereNotIn('orders.status', Order::VOID);

        $byDepartment = $valid()->join('products', 'products.id', '=', 'order_items.product_id')
            ->selectRaw('products.department, SUM(order_items.qty) units, SUM(order_items.price * order_items.qty) sales, SUM((order_items.price - order_items.cost) * order_items.qty) profit')
            ->groupBy('products.department')->orderByDesc('sales')->get();

        return view('admin.reports', [
            'days' => $days,
            'byDepartment' => $byDepartment,
            'byCity' => Order::where('created_at', '>=', $since)
                ->selectRaw("city, COUNT(*) orders, SUM(CASE WHEN status NOT IN ('cancelled','returned') THEN total ELSE 0 END) sales, SUM(CASE WHEN status = 'returned' THEN 1 ELSE 0 END) rto")
                ->groupBy('city')->orderByDesc('sales')->limit(10)->get(),
            'byStatus' => Order::where('created_at', '>=', $since)->selectRaw('status, COUNT(*) c, SUM(total) total')->groupBy('status')->get()->keyBy('status'),
            'top' => $valid()->selectRaw('order_items.product_id, order_items.title, order_items.sku, SUM(order_items.qty) units, SUM(order_items.price * order_items.qty) sales, SUM((order_items.price - order_items.cost) * order_items.qty) profit')
                ->groupBy('order_items.product_id', 'order_items.title', 'order_items.sku')->orderByDesc('units')->limit(10)->get(),
            'winners' => Product::where('is_winner', true)->pluck('id')->flip(),
            'coupons' => Order::where('created_at', '>=', $since)->whereNotNull('coupon_code')
                ->selectRaw('coupon_code, COUNT(*) uses, SUM(discount) discount')->groupBy('coupon_code')->get(),
        ]);
    }

    public function exportOrders(Request $request)
    {
        $days = in_array($request->integer('days'), [7, 30, 90], true) ? $request->integer('days') : 30;

        // Customer-entered text must not be read as a formula when the file is opened in Excel.
        $safe = fn ($value) => is_string($value) && preg_match('/^[=+@\t\r-]/', $value) ? "'".$value : $value;

        return response()->streamDownload(function () use ($days, $safe) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Order', 'Date', 'Status', 'Customer', 'Phone', 'City', 'Items', 'Subtotal', 'Discount', 'Coupon', 'Delivery', 'Total', 'Courier', 'Tracking']);
            Order::withSum('items', 'qty')->where('created_at', '>=', now()->subDays($days)->startOfDay())->orderBy('id')
                ->chunk(200, function ($orders) use ($out, $safe) {
                    foreach ($orders as $o) {
                        fputcsv($out, array_map($safe, [$o->number, $o->created_at->format('Y-m-d H:i'), $o->status_label, $o->name, $o->phone, $o->city, $o->items_sum_qty, $o->subtotal, $o->discount, $o->coupon_code, $o->shipping, $o->total, $o->courier, $o->tracking_no]));
                    }
                });
            fclose($out);
        }, 'orders-last-'.$days.'-days.csv', ['Content-Type' => 'text/csv']);
    }

    /* ---------- Staff & permissions ---------- */

    public function staff()
    {
        return view('admin.staff', [
            'staff' => User::where('role', '!=', 'customer')->orderByRaw("role = 'owner' DESC")->orderBy('name')->get(),
            'matrix' => RolePermission::all()->groupBy('role')->map->pluck('section'),
        ]);
    }

    public function storeStaff(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'unique:users,email'],
            'role' => ['required', Rule::in(array_keys(config('store.roles')))],
            'password' => ['required', Password::min(8)],
        ]);
        User::create($data);

        return back()->with('toast', 'Staff member added.');
    }

    public function updateStaff(Request $request, User $user)
    {
        abort_if($user->role === 'customer', 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'role' => ['required', Rule::in(array_keys(config('store.roles')))],
            'password' => ['nullable', Password::min(8)],
        ]);
        $active = $request->boolean('is_active');

        // Never leave the store without an active owner.
        $losingOwner = $user->role === 'owner' && ($data['role'] !== 'owner' || ! $active);
        if ($losingOwner && User::where('role', 'owner')->where('is_active', true)->whereKeyNot($user->id)->doesntExist()) {
            return back()->withErrors(['role' => 'At least one active owner is required.']);
        }

        $user->update(array_filter(['name' => $data['name'], 'role' => $data['role'], 'password' => $data['password'] ?? null]) + ['is_active' => $active]);

        return back()->with('toast', 'Staff member saved.');
    }

    public function updatePermissions(Request $request)
    {
        $sections = array_keys(config('store.sections'));
        $roles = array_diff(array_keys(config('store.roles')), ['owner']);

        DB::transaction(function () use ($request, $sections, $roles) {
            RolePermission::whereIn('role', $roles)->delete();
            foreach ($roles as $role) {
                foreach (array_intersect((array) $request->input("perm.$role", []), $sections) as $section) {
                    RolePermission::create(compact('role', 'section'));
                }
            }
        });

        return back()->with('toast', 'Permissions updated.');
    }
}
