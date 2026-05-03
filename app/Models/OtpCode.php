<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    public const PURPOSE_REGISTER = 'register';
    public const PURPOSE_LOGIN = 'login';
    public const PURPOSE_FORGOT_PASSWORD = 'forgot_password';

    protected $fillable = [
        'purpose',
        'identifier',
        'payload',
        'otp_hash',
        'verification_token',
        'expires_at',
        'verified_at',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }
}
