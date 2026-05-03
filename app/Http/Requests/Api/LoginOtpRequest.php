<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class LoginOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'identifier' => $this->input('identifier', $this->input('mobile')),
        ]);
    }

    public function rules(): array
    {
        return [
            'identifier' => 'required|string|max:255',
        ];
    }
}
