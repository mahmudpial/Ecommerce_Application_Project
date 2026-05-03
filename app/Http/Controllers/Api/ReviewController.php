<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ReviewResource;
use App\Models\Review;
use App\Models\Product;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    // প্রোডাক্টের সব রিভিউ দেখা
    public function index($productId)
    {
        $product = Product::findOrFail($productId);

        $reviews = Review::with(['user', 'product'])
            ->where('product_id', $productId)
            ->approved()
            ->latest()
            ->paginate(10);

        return response()->json([
            'average_rating' => $product->average_rating,
            'total_reviews' => $product->reviews_count,
            'rating_distribution' => $product->rating_distribution,
            'reviews' => ReviewResource::collection($reviews)
        ]);
    }

    // রিভিউ দেওয়া
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'comment' => 'required|string|min:5'
        ]);

        // আগে রিভিউ দিয়ে থাকলে আপডেট করবে
        $review = Review::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'product_id' => $request->product_id
            ],
            [
                'rating' => $request->rating,
                'title' => $request->title,
                'comment' => $request->comment,
                'is_approved' => false // নতুন রিভিউ মডারেশনের জন্য পেন্ডিং
            ]
        );

        return response()->json([
            'message' => 'Review submitted successfully. Waiting for approval.',
            'review' => new ReviewResource($review->load(['user', 'product']))
        ], 201);
    }

    // আমার দেওয়া রিভিউ দেখা
    public function myReviews(Request $request)
    {
        $reviews = Review::with(['product', 'user'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return ReviewResource::collection($reviews);
    }

    // রিভিউ ডিলিট
    public function remove(Request $request, $id)
    {
        $review = Review::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $review->delete();

        return response()->json(['message' => 'Review deleted successfully']);
    }

    // Pending reviews for moderation
    public function pendingReviews()
    {
        $reviews = Review::with(['user', 'product'])
            ->pending()
            ->latest()
            ->paginate(10);

        return response()->json($reviews);
    }

    // Approve a review
    public function approve(Review $review)
    {
        $review->update([
            'is_approved' => true,
        ]);

        return response()->json([
            'message' => 'Review approved successfully',
            'review' => new ReviewResource($review->fresh(['user', 'product'])),
        ]);
    }

    // Reject a review
    public function reject(Request $request, Review $review)
    {
        $validated = $request->validate([
            'admin_reply' => 'nullable|string|max:1000',
        ]);

        $review->update([
            'is_approved' => false,
            'admin_reply' => $validated['admin_reply'] ?? null,
        ]);

        return response()->json([
            'message' => 'Review rejected successfully',
            'review' => new ReviewResource($review->fresh(['user', 'product'])),
        ]);
    }
}
