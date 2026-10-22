<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ReturnRequest;
use App\Models\Review;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $since = now()->subDays(30)->startOfDay();
        $orders = Order::where('created_at', '>=', $since)->get();
        $valid = $orders->whereNotIn('status', Order::VOID);
        $revenue = (int) $valid->sum('total');
        $cost = (int) OrderItem::whereIn('order_id', $valid->pluck('id'))->sum(DB::raw('cost * qty'));
        $goods = (int) $valid->sum(fn ($o) => $o->subtotal - $o->discount);
        $closed = $orders->whereIn('status', ['delivered', 'returned'])->count();

        $days = collect(range(13, 0))->map(function ($ago) use ($valid) {
            $day = now()->subDays($ago);
            $rows = $valid->filter(fn ($o) => $o->created_at->isSameDay($day));

            return ['label' => $day->format('j M'), 'short' => $day->format('j'), 'total' => (int) $rows->sum('total'), 'orders' => $rows->count()];
        });

        $stale = Product::staleCutoff();

        return view('admin.dashboard', [
            'kpis' => [
                ['Revenue (30 days)', money($revenue), 'banknote', $valid->count().' orders excluding cancelled and returned'],
                ['Average order value', money($valid->count() ? $revenue / $valid->count() : 0), 'receipt', 'Including delivery charges'],
                ['Gross margin', $goods ? round(($goods - $cost) / $goods * 100, 1).'%' : '—', 'trending-up', money($goods - $cost).' profit before delivery and ads'],
                ['RTO rate', $closed ? round($orders->where('status', 'returned')->count() / $closed * 100, 1).'%' : '—', 'undo-2', 'Returned parcels out of completed orders'],
            ],
            'days' => $days,
            'maxDay' => max($days->max('total'), 1),
            'queue' => [
                ['Orders awaiting confirmation', Order::where('status', 'pending')->count(), route('admin.orders.index', ['status' => 'pending']), 'phone-call', 'orders'],
                ['Stock needs re-verification', $stale ? Product::whereIn('status', Product::SELLABLE)->where(fn ($q) => $q->whereNull('stock_verified_at')->orWhere('stock_verified_at', '<', $stale))->count() : 0, route('admin.stock.index', ['state' => 'stale']), 'clock-alert', 'stock'],
                ['Low stock products', Product::purchasable()->where('stock_qty', '<=', config('store.low_stock_at'))->count(), route('admin.stock.index', ['state' => 'low']), 'triangle-alert', 'stock'],
                ['Customers waiting for restock', \App\Models\StockAlert::whereNull('notified_at')->count(), route('admin.stock.index').'#waiting', 'bell-ring', 'stock'],
                ['Open return requests', ReturnRequest::where('status', 'requested')->count(), route('admin.returns.index'), 'undo-2', 'returns'],
                ['Reviews awaiting approval', Review::where('status', 'pending')->count(), route('admin.reviews.index', ['status' => 'pending']), 'star', 'reviews'],
            ],
            'recent' => Order::latest()->limit(8)->get(),
            'top' => OrderItem::whereHas('order', fn ($o) => $o->where('created_at', '>=', $since)->whereNotIn('status', Order::VOID))
                ->selectRaw('title, SUM(qty) units, SUM(price * qty) sales')->groupBy('title')->orderByDesc('units')->limit(6)->get(),
            'messages' => ContactMessage::latest()->limit(5)->get(),
        ]);
    }
}
