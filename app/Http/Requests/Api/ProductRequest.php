<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id;
        $isCreate = $this->isMethod('post');

        return [
            'name' => $isCreate ? 'required|string|max:255' : 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'images' => 'nullable',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'slug')->ignore($productId),
            ],
            'stock' => $isCreate ? 'nullable|integer|min:0' : 'sometimes|integer|min:0',
            'price' => 'nullable|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'is_featured' => $isCreate ? 'sometimes|boolean' : 'sometimes|boolean',
            'brand_id' => $isCreate ? 'required|exists:brands,id' : 'sometimes|exists:brands,id',
            'category_id' => $isCreate ? 'required|exists:categories,id' : 'sometimes|exists:categories,id',
            'is_active' => $isCreate ? 'sometimes|boolean' : 'sometimes|boolean',
        ];
    }
}
