<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $brandId = $this->route('brand')?->id;
        $isCreate = $this->isMethod('post');

        return [
            'name' => $isCreate ? 'required|string|max:255' : 'sometimes|required|string|max:255',
            'logo' => 'nullable|string|max:2048',
            'description' => 'nullable|string',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('brands', 'slug')->ignore($brandId),
            ],
            'is_active' => $isCreate ? 'sometimes|boolean' : 'sometimes|boolean',
        ];
    }
}
