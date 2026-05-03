<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'identifier' => $this->input('identifier', $this->input('mobile')),
            'purpose' => $this->input('purpose', 'login'),
        ]);
    }

    public function rules(): array
    {
        return [
            'purpose' => 'required|in:register,login,forgot_password',
            'identifier' => 'required|string|max:255',
            'otp' => 'required|string|size:6',
        ];
    }
}
