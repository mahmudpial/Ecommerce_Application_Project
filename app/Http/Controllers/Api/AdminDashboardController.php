<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\OrderResource;
use App\Http\Resources\Api\ProductResource;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        $today = Carbon::now()->startOfDay();
        $sevenDaysAgo = Carbon::now()->subDays(7);
        $dashboardLimit = max(1, min((int) $request->integer('per_page', 5), 10));

        $revenue = (float) Order::where('payment_status', 'paid')->sum('total');
        $sales = [
            'revenue' => $revenue,
            'orders' => Order::count(),
            'pending_orders' => Order::where('order_status', 'pending')->count(),
            'paid_orders' => Order::where('payment_status', 'paid')->count(),
        ];

        $catalog = [
            'products' => Product::count(),
            'active_products' => Product::where('is_active', true)->count(),
        ];

        $people = [
            'users' => User::count(),
            'admins' => User::where('role', 'admin')->count(),
            'moderators' => User::where('role', 'moderator')->count(),
            'customers' => User::where('role', 'user')->count(),
        ];

        $content = [
            'reviews' => Review::count(),
            'pending_reviews' => Review::pending()->count(),
            'invoices' => Invoice::count(),
        ];

        $activity = [
            'orders_today' => Order::where('created_at', '>=', $today)->count(),
            'reviews_today' => Review::where('created_at', '>=', $today)->count(),
            'products_added_today' => Product::where('created_at', '>=', $today)->count(),
            'orders_last_7_days' => Order::where('created_at', '>=', $sevenDaysAgo)->count(),
            'reviews_last_7_days' => Review::where('created_at', '>=', $sevenDaysAgo)->count(),
            'products_added_last_7_days' => Product::where('created_at', '>=', $sevenDaysAgo)->count(),
        ];

        $recentOrders = Order::with('user')
            ->latest()
            ->limit($dashboardLimit)
            ->get();

        $topProducts = Product::with(['brand', 'category'])
            ->orderByDesc('view_count')
            ->limit($dashboardLimit)
            ->get();

        return response()->json([
            'sales' => $sales,
            'catalog' => $catalog,
            'people' => $people,
            'content' => $content,
            'activity' => $activity,
            'recent_orders' => OrderResource::collection($recentOrders),
            'top_products' => ProductResource::collection($topProducts),
        ]);
    }
}
