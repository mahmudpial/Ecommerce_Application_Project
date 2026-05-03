<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('category')?->id;
        $isCreate = $this->isMethod('post');

        return [
            'name' => $isCreate ? 'required|string|max:255' : 'sometimes|required|string|max:255',
            'image' => 'nullable|string|max:2048',
            'parent_id' => 'nullable|exists:categories,id',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('categories', 'slug')->ignore($categoryId),
            ],
            'is_active' => $isCreate ? 'sometimes|boolean' : 'sometimes|boolean',
        ];
    }
}
