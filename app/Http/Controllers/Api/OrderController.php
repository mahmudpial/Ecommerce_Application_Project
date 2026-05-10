<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = $request->user()
            ->orders()
            ->with(['user', 'items.product'])
            ->latest()
            ->get();

        return OrderResource::collection($orders);
    }

    public function show(Request $request, string $order)
    {
        $record = $request->user()
            ->orders()
            ->with(['user', 'items.product'])
            ->where(function ($query) use ($order) {
                $query->where('id', $order)
                    ->orWhere('order_number', $order);
            })
            ->first();

        if (!$record) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json([
            'order' => new OrderResource($record),
        ]);
    }

    public function adminIndex(Request $request)
    {
        $query = Order::with('user');

        // Search by order number, customer info, phone, or transaction ID
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhere('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhere('transaction_id', 'like', "%{$search}%")
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

        return OrderResource::collection($orders);
    }

    public function adminShow(Order $order)
    {
        return response()->json([
            'order' => new OrderResource($order->load(['user', 'items.product'])),
        ]);
    }

    public function update(Request $request, Order $order)
    {
        $data = $request->validate([
            'order_status' => 'required|string|in:pending,processing,shipped,delivered,cancelled',
        ]);

        $order->update([
            'order_status' => $data['order_status'],
        ]);

        return response()->json([
            'message' => 'Order status updated successfully',
            'order' => new OrderResource($order->fresh()->load(['user', 'items.product'])),
        ]);
    }
    // For admin to generate PDF invoice
    public function generateInvoice(Order $order)
    {
        $order->load(['user', 'items.product']);
        $subtotal = 0;
        foreach ($order->items as $item) {
            $subtotal += $item->price * $item->quantity;
        }
        $shippingCost = $order->shipping_cost ?? 0;
        $discount = $order->discount ?? 0;
        $tax = $order->tax ?? 0;
        $total = $subtotal + $shippingCost + $tax - $discount;

        $pdf = Pdf::loadView('pdf.invoice', [
            'order' => $order,
            'subtotal' => $subtotal,
            'shipping_cost' => $shippingCost,
            'discount' => $discount,
            'tax' => $tax,
            'total' => $total,
        ]);
        return $pdf->download("invoice-{$order->id}.pdf");
    }
}
