<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $linkedUser = $this->relationLoaded('user') ? $this->user : null;

        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'customer_name' => $this->customer_name ?: $linkedUser?->name,
            'customer_email' => $this->customer_email ?: $linkedUser?->email,
            'customer_phone' => $this->customer_phone ?: $linkedUser?->mobile,
            'subtotal' => $this->subtotal,
            'shipping_cost' => $this->shipping_cost,
            'shipping_fee' => $this->shipping_cost,
            'discount' => $this->discount,
            'total' => $this->total,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'transaction_id' => $this->transaction_id,
            'payment_label' => $this->resolvePaymentLabel(),
            'order_status' => $this->order_status,
            'status' => $this->order_status,
            'delivery_label' => $this->resolveDeliveryLabel(),
            'shipping_address' => $this->shipping_address,
            'address_label' => $this->shipping_address,
            'item_count' => $this->relationLoaded('items') ? $this->items->sum('quantity') : 0,
            'items' => $this->whenLoaded('items', fn () => OrderItemResource::collection($this->items)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'user' => UserResource::make($this->whenLoaded('user')),
        ];
    }

    private function resolvePaymentLabel(): string
    {
        return match (strtolower((string) $this->payment_method)) {
            'bkash', 'nagad' => 'Mobile wallet',
            'card' => 'Card payment',
            'sslcommerz' => 'SSLCommerz',
            'cod' => 'Cash on delivery',
            default => 'Cash on delivery',
        };
    }

    private function resolveDeliveryLabel(): string
    {
        $shipping = (float) $this->shipping_cost;

        if ($shipping <= 0) {
            return 'Standard delivery';
        }

        if ($shipping <= 120) {
            return 'Standard delivery';
        }

        if ($shipping <= 220) {
            return 'Express delivery';
        }

        return 'Same day delivery';
    }
}
