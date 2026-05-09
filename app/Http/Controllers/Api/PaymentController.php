<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\OrderResource;
use App\Models\Invoice;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Raziul\Sslcommerz\Facades\Sslcommerz;

class PaymentController extends Controller
{
    public function checkout(Request $request)
    {
        $request->validate([
            'shipping_address' => 'required|string',
            'customer_name' => 'required|string',
            'customer_email' => 'required|email',
            'customer_phone' => 'required|string',
            'delivery_method' => 'nullable|string|in:standard,express,same_day',
            'payment_method' => 'nullable|string|in:cod,bkash,card',
            'items' => 'nullable|array',
            'items.*.product_id' => 'nullable|integer',
            'items.*.id' => 'nullable|integer',
            'items.*.quantity' => 'required_with:items|integer|min:1',
        ]);

        $user = $request->user() ?: User::find($request->input('user_id'));

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $lineItems = $this->resolveCheckoutItems($request, $user);

        if ($lineItems->isEmpty()) {
            return response()->json(['message' => 'Cart is empty'], 400);
        }

        $subtotal = (float) $lineItems->sum(fn ($item) => $item['unit_price'] * $item['quantity']);
        $shippingCost = $this->resolveShippingCost($request->input('delivery_method', 'standard'), $subtotal);
        $discount = 0;
        $total = $subtotal + $shippingCost - $discount;
        $paymentMethod = $request->input('payment_method', 'cod');

        $order = Order::create([
            'user_id' => $user->id,
            'subtotal' => $subtotal,
            'shipping_cost' => $shippingCost,
            'discount' => $discount,
            'total' => $total,
            'shipping_address' => $request->shipping_address,
            'customer_name' => $request->customer_name,
            'customer_email' => $request->customer_email,
            'customer_phone' => $request->customer_phone,
            'payment_status' => 'pending',
            'order_status' => 'processing',
            'payment_method' => $paymentMethod,
        ]);

        foreach ($lineItems as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'price' => $item['unit_price'],
                'quantity' => $item['quantity'],
                'total' => $item['line_total'],
            ]);
        }

        Cart::where('user_id', $user->id)->delete();

        return response()->json([
            'message' => 'Order created successfully',
            'order' => new OrderResource($order->load(['items.product'])),
        ], 201);
    }

    // পেমেন্ট সাকসেস কলব্যাক
    public function paymentSuccess(Request $request)
    {
        $transactionId = $request->input('tran_id');

        // অর্ডার খুঁজে বের করুন
        $order = Order::where('order_number', $transactionId)->first();

        if (!$order) {
            return redirect()->away(env('FRONTEND_URL') . '/payment/failed');
        }

        // ✅ সঠিকভাবে amount সহ validatePayment() কল করুন
        $isValid = Sslcommerz::validatePayment($request->all(), $transactionId, (float) $order->total);

        if ($isValid) {
            $order->update([
                'payment_status' => 'paid',
                'order_status' => 'processing',
                'transaction_id' => $request->input('bank_tran_id')
            ]);

            // কার্ট ক্লিয়ার
            Cart::where('user_id', $order->user_id)->delete();

            $invoiceData = [
                'order_id' => $order->id,
                'pdf_path' => $this->generateInvoicePdf($order),
            ];

            Invoice::create($invoiceData);

            return redirect()->away(env('FRONTEND_URL') . '/payment/success?order=' . $order->order_number);
        }

        return redirect()->away(env('FRONTEND_URL') . '/payment/failed');
    }

    // পেমেন্ট ফেইল কলব্যাক
    public function paymentFailure(Request $request)
    {
        $orderNumber = $request->input('tran_id');
        $order = Order::where('order_number', $orderNumber)->first();

        if ($order) {
            $order->update([
                'payment_status' => 'failed',
                'order_status' => 'cancelled'
            ]);
        }

        return redirect()->away(env('FRONTEND_URL') . '/payment/failed');
    }

    // পেমেন্ট ক্যান্সেল কলব্যাক
    public function paymentCancel(Request $request)
    {
        return redirect()->away(env('FRONTEND_URL') . '/payment/cancel');
    }

    // ✅ IPN Handler 
    public function paymentIpn(Request $request)
    {
        \Log::info('SSLCommerz IPN Called', $request->all());

        $transactionId = $request->input('tran_id');

        if (!$transactionId) {
            return response()->json(['status' => 'error', 'message' => 'No transaction ID'], 400);
        }

        $order = Order::where('order_number', $transactionId)->first();

        if (!$order) {
            return response()->json(['status' => 'error', 'message' => 'Order not found'], 404);
        }

        // ✅ সঠিকভাবে amount সহ validatePayment() কল করুন
        $isValid = Sslcommerz::validatePayment($request->all(), $transactionId, (float) $order->total);

        if ($isValid) {
            if ($order->payment_status !== 'paid') {
                $order->update([
                    'payment_status' => 'paid',
                    'order_status' => 'processing',
                    'transaction_id' => $request->input('bank_tran_id', $request->input('tran_id'))
                ]);

                // কার্ট ক্লিয়ার
                Cart::where('user_id', $order->user_id)->delete();

                \Log::info('Order paid via IPN', ['order_id' => $order->id]);
            }

            return response()->json(['status' => 'success']);
        }

        \Log::error('IPN Validation Failed', ['tran_id' => $transactionId]);

        return response()->json(['status' => 'failed'], 400);
    }

    public function generateInvoicePdf($order)
    {
        // Pdf::
        //     $pdf = Pdf::loadView('pdf.invoice', $data);
        // return $pdf->download('invoice.pdf');
        return 'pdf path';
    }

    private function resolveCheckoutItems(Request $request, User $user)
    {
        $requestedItems = collect($request->input('items', []))
            ->filter(fn ($item) => is_array($item) && ($item['product_id'] ?? $item['id'] ?? null))
            ->values();

        if ($requestedItems->isNotEmpty()) {
            $productIds = $requestedItems
                ->map(fn ($item) => (int) ($item['product_id'] ?? $item['id']))
                ->filter()
                ->unique()
                ->values();

            $products = Product::whereIn('id', $productIds)
                ->where('is_active', true)
                ->get()
                ->keyBy('id');

            foreach ($productIds as $productId) {
                if (! $products->has($productId)) {
                    throw ValidationException::withMessages([
                        'items' => ["Product {$productId} could not be found."],
                    ]);
                }
            }

            return $requestedItems->map(function ($item) use ($products) {
                $productId = (int) ($item['product_id'] ?? $item['id']);
                $product = $products->get($productId);
                $quantity = max(1, (int) ($item['quantity'] ?? 1));
                $unitPrice = $this->resolveProductUnitPrice($product);

                return [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_price' => $unitPrice,
                    'quantity' => $quantity,
                    'line_total' => $unitPrice * $quantity,
                ];
            });
        }

        $cartItems = Cart::with('product')
            ->where('user_id', $user->id)
            ->get();

        if ($cartItems->isEmpty()) {
            return collect();
        }

        return $cartItems->map(function ($item) {
            if (! $item->product || ! $item->product->is_active) {
                throw ValidationException::withMessages([
                    'items' => ['Some cart items are no longer available.'],
                ]);
            }

            $unitPrice = $this->resolveProductUnitPrice($item->product);
            $quantity = max(1, (int) $item->quantity);

            return [
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'line_total' => $unitPrice * $quantity,
            ];
        });
    }

    private function resolveProductUnitPrice(Product $product): float
    {
        $price = (float) ($product->price ?? 0);
        $discounted = (float) ($product->discount_price ?? 0);

        if ($discounted > 0 && $discounted < $price) {
            return $discounted;
        }

        return $price;
    }

    private function resolveShippingCost(string $deliveryMethod, float $subtotal): float
    {
        return match ($deliveryMethod) {
            'express' => 220,
            'same_day' => 350,
            default => $subtotal >= 3000 ? 0 : 120,
        };
    }
}
