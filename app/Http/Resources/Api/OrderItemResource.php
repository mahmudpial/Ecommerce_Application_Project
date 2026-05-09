<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,
            'name' => $this->product_name,
            'price' => $this->price,
            'quantity' => $this->quantity,
            'total' => $this->total,
            'images' => $this->whenLoaded('product', fn () => $this->product?->images),
            'slug' => $this->whenLoaded('product', fn () => $this->product?->slug),
            'product' => $this->whenLoaded('product', fn () => ProductResource::make($this->product)),
        ];
    }
}
