<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ReviewResource;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ModeratorDashboardController extends Controller
{
    public function index(Request $request)
    {
        $today = Carbon::now()->startOfDay();
        $sevenDaysAgo = Carbon::now()->subDays(7);
        $dashboardLimit = max(1, min((int) $request->integer('per_page', 5), 10));

        $moderation = [
            'total_reviews' => Review::count(),
            'pending_reviews' => Review::pending()->count(),
            'approved_reviews' => Review::approved()->count(),
        ];

        $catalog = [
            'products' => Product::count(),
            'active_products' => Product::where('is_active', true)->count(),
        ];

        $activity = [
            'pending_reviews_today' => Review::pending()
                ->where('created_at', '>=', $today)
                ->count(),
            'approved_reviews_today' => Review::approved()
                ->where('created_at', '>=', $today)
                ->count(),
            'pending_reviews_last_7_days' => Review::pending()
                ->where('created_at', '>=', $sevenDaysAgo)
                ->count(),
            'approved_reviews_last_7_days' => Review::approved()
                ->where('created_at', '>=', $sevenDaysAgo)
                ->count(),
        ];

        $recentPendingReviews = Review::with(['user', 'product'])
            ->pending()
            ->latest()
            ->limit($dashboardLimit)
            ->get();

        return response()->json([
            'moderation' => $moderation,
            'catalog' => $catalog,
            'activity' => $activity,
            'recent_pending_reviews' => ReviewResource::collection($recentPendingReviews),
        ]);
    }
}
