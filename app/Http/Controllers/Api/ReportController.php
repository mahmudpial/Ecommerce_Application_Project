<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function sales(Request $request)
    {
        $period = $request->get('period', 'daily'); // daily, monthly, yearly
        $date = $request->get('date', now()->toDateString());

        $query = Order::query();

        switch ($period) {
            case 'daily':
                $query->whereDate('created_at', $date);
                break;
            case 'monthly':
                $query->whereYear('created_at', substr($date, 0, 4))
                    ->whereMonth('created_at', substr($date, 5, 2));
                break;
            case 'yearly':
                $query->whereYear('created_at', $date);
                break;
        }

        $totalOrders = $query->count();
        $totalRevenue = $query->sum('total');
        $avgOrderValue = $totalOrders ? $totalRevenue / $totalOrders : 0;

        // Group by day/month for trend
        $trend = Order::select(DB::raw("DATE(created_at) as date"), DB::raw("SUM(total) as revenue"), DB::raw("COUNT(*) as orders"))
            ->when($period == 'monthly', function ($q) use ($date) {
                return $q->whereYear('created_at', substr($date, 0, 4))
                    ->whereMonth('created_at', substr($date, 5, 2))
                    ->groupBy('date');
            }, function ($q) use ($period, $date) {
                if ($period == 'daily')
                    return $q->whereDate('created_at', $date)->groupBy('date');
                if ($period == 'yearly')
                    return $q->whereYear('created_at', $date)
                        ->groupBy(DB::raw("MONTH(created_at)"));
            })->get();

        return response()->json([
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue,
            'avg_order_value' => round($avgOrderValue, 2),
            'trend' => $trend
        ]);
    }

    public function products(Request $request)
    {
        $limit = $request->get('limit', 10);
        $topProducts = Product::withCount('orderItems')
            ->withSum('orderItems', 'price')
            ->orderByDesc('order_items_sum_price')
            ->limit($limit)
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'total_sold' => $p->order_items_count,
                'revenue' => $p->order_items_sum_price ?? 0
            ]);
        return response()->json($topProducts);
    }

    public function customers(Request $request)
    {
        $topCustomers = User::withCount('orders')
            ->withSum('orders', 'total')
            ->orderByDesc('orders_sum_total')
            ->limit(10)
            ->get()
            ->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'total_orders' => $u->orders_count,
                'total_spent' => $u->orders_sum_total ?? 0
            ]);
        return response()->json($topCustomers);
    }

    public function orders(Request $request)
    {
        $status = $request->get('status');
        $query = Order::with('user');
        if ($status)
            $query->where('order_status', $status);
        $orders = $query->latest()->paginate(20);
        return response()->json($orders);
    }
}