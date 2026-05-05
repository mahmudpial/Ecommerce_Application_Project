<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function adminIndex(Request $request)
    {
        $query = Order::with('user'); // eager load user for customer info

        // Search by order ID or customer name/email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhere('order_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by order status
        if ($request->filled('status')) {
            $query->where('order_status', $request->status);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(20);

        // Transform the data to match what the frontend expects
        $orders->getCollection()->transform(function ($order) {
            return [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->user?->name ?? 'Guest',
                'customer_phone' => $order->user?->phone ?? null,
                'customer_email' => $order->user?->email ?? null,
                'total' => $order->total,
                'subtotal' => $order->subtotal,
                'discount' => $order->discount,
                'payment_status' => $order->payment_status,
                'order_status' => $order->order_status, // note: frontend uses both 'status' and 'order_status'
                'status' => $order->order_status,       // alias for compatibility
                'created_at' => $order->created_at->toISOString(),
            ];
        });

        return response()->json($orders);
    }
}