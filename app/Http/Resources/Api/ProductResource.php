<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'images' => $this->decodedImages(),
            'slug' => $this->slug,
            'stock' => $this->stock,
            'price' => $this->price,
            'discount_price' => $this->discount_price,
            'is_featured' => (bool) $this->is_featured,
            'view_count' => $this->view_count,
            'is_active' => (bool) $this->is_active,
            'average_rating' => $this->average_rating,
            'rating_distribution' => $this->rating_distribution,
            'reviews_count' => $this->reviews_count,
            'brand' => BrandResource::make($this->whenLoaded('brand')),
            'category' => CategoryResource::make($this->whenLoaded('category')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function decodedImages(): mixed
    {
        if (is_array($this->images)) {
            return $this->images;
        }

        if (is_string($this->images) && $this->images !== '') {
            $decoded = json_decode($this->images, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return $this->images;
    }
}
