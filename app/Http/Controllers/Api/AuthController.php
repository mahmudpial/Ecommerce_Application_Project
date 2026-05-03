<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ForgotPasswordOtpRequest;
use App\Http\Requests\Api\LoginOtpRequest;
use App\Http\Requests\Api\RegisterOtpRequest;
use App\Http\Requests\Api\ResetPasswordRequest;
use App\Http\Requests\Api\VerifyOtpRequest;
use App\Http\Resources\Api\UserResource;
use App\Mail\OtpMail;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function registerRequestOtp(RegisterOtpRequest $request)
    {
        $data = $request->validated();
        $identifier = $this->normalizeIdentifier($data['email']);

        [$otpCode, $otp] = $this->createOtpCode(
            OtpCode::PURPOSE_REGISTER,
            $identifier,
            [
                'name' => $data['name'],
                'email' => $this->normalizeIdentifier($data['email']),
                'mobile' => $data['mobile'],
                'password_hash' => Hash::make($data['password']),
            ]
        );

        $deliveryChannel = $this->deliverOtp($otp, $data['email'], $data['name'], 'registration');

        return response()->json([
            'message' => 'Registration OTP sent successfully',
            'purpose' => $otpCode->purpose,
            'identifier' => $identifier,
            'delivery_channel' => $deliveryChannel,
            'expires_in_minutes' => 10,
            'otp' => app()->isLocal() ? $otp : null,
        ]);
    }

    public function registerVerifyOtp(VerifyOtpRequest $request)
    {
        $validated = $request->validated();
        $identifier = $this->normalizeIdentifier($validated['identifier']);

        $otpCode = $this->findValidOtpCode(
            OtpCode::PURPOSE_REGISTER,
            $identifier,
            $validated['otp']
        );

        if (! $otpCode) {
            return response()->json(['message' => 'Invalid or expired OTP'], 401);
        }

        $payload = $otpCode->payload ?? [];

        DB::table('users')->insert([
            'name' => $payload['name'],
            'email' => $payload['email'],
            'mobile' => $payload['mobile'],
            'password' => $payload['password_hash'],
            'role' => 'user',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::where('email', $payload['email'])->firstOrFail();

        $otpCode->update([
            'used_at' => now(),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful',
            'user' => new UserResource($user),
            'token' => $token,
        ], 201);
    }

    public function loginRequestOtp(LoginOtpRequest $request)
    {
        $validated = $request->validated();
        $identifier = $this->normalizeIdentifier($validated['identifier']);
        $user = $this->resolveUserByIdentifier($identifier);

        if (! $user) {
            return response()->json(['message' => 'Account not found'], 404);
        }

        [$otpCode, $otp] = $this->createOtpCode(
            OtpCode::PURPOSE_LOGIN,
            $identifier
        );

        $deliveryChannel = $this->deliverOtp($otp, $user->email, $user->name, $identifier);

        return response()->json([
            'message' => 'OTP sent successfully',
            'purpose' => $otpCode->purpose,
            'identifier' => $identifier,
            'delivery_channel' => $deliveryChannel,
            'expires_in_minutes' => 10,
            'otp' => app()->isLocal() ? $otp : null,
        ]);
    }

    public function loginVerifyOtp(VerifyOtpRequest $request)
    {
        $validated = $request->validated();
        $identifier = $this->normalizeIdentifier($validated['identifier']);

        $otpCode = $this->findValidOtpCode(
            OtpCode::PURPOSE_LOGIN,
            $identifier,
            $validated['otp']
        );

        if (! $otpCode) {
            return response()->json(['message' => 'Invalid or expired OTP'], 401);
        }

        $user = $this->resolveUserByIdentifier($identifier);

        if (! $user) {
            return response()->json(['message' => 'Account not found'], 404);
        }

        $otpCode->update([
            'used_at' => now(),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    public function forgotPasswordRequestOtp(ForgotPasswordOtpRequest $request)
    {
        $validated = $request->validated();
        $identifier = $this->normalizeIdentifier($validated['identifier']);
        $user = $this->resolveUserByIdentifier($identifier);

        if (! $user) {
            return response()->json(['message' => 'Account not found'], 404);
        }

        [$otpCode, $otp] = $this->createOtpCode(
            OtpCode::PURPOSE_FORGOT_PASSWORD,
            $identifier
        );

        $deliveryChannel = $this->deliverOtp($otp, $user->email, $user->name, $identifier);

        return response()->json([
            'message' => 'Password reset OTP sent successfully',
            'purpose' => $otpCode->purpose,
            'identifier' => $identifier,
            'delivery_channel' => $deliveryChannel,
            'expires_in_minutes' => 10,
            'otp' => app()->isLocal() ? $otp : null,
        ]);
    }

    public function forgotPasswordVerifyOtp(VerifyOtpRequest $request)
    {
        $validated = $request->validated();
        $identifier = $this->normalizeIdentifier($validated['identifier']);

        $otpCode = $this->findValidOtpCode(
            OtpCode::PURPOSE_FORGOT_PASSWORD,
            $identifier,
            $validated['otp']
        );

        if (! $otpCode) {
            return response()->json(['message' => 'Invalid or expired OTP'], 401);
        }

        $resetToken = (string) Str::uuid();

        $otpCode->update([
            'verified_at' => now(),
            'verification_token' => $resetToken,
        ]);

        return response()->json([
            'message' => 'OTP verified successfully',
            'reset_token' => $resetToken,
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $validated = $request->validated();

        $otpCode = OtpCode::where('verification_token', $validated['reset_token'])
            ->where('purpose', OtpCode::PURPOSE_FORGOT_PASSWORD)
            ->whereNotNull('verified_at')
            ->whereNull('used_at')
            ->first();

        if (! $otpCode) {
            return response()->json(['message' => 'Invalid or expired reset token'], 401);
        }

        $user = $this->resolveUserByIdentifier($otpCode->identifier);

        if (! $user) {
            return response()->json(['message' => 'Account not found'], 404);
        }

        $user->update([
            'password' => $validated['password'],
        ]);

        $otpCode->update([
            'used_at' => now(),
        ]);

        return response()->json([
            'message' => 'Password reset successfully',
        ]);
    }

    public function sendOtp(LoginOtpRequest $request)
    {
        return $this->loginRequestOtp($request);
    }

    public function verifyOtp(VerifyOtpRequest $request)
    {
        return $this->loginVerifyOtp($request);
    }

    private function createOtpCode(string $purpose, string $identifier, array $payload = []): array
    {
        $otp = (string) random_int(100000, 999999);

        $otpCode = OtpCode::create([
            'purpose' => $purpose,
            'identifier' => $this->normalizeIdentifier($identifier),
            'payload' => $payload ?: null,
            'otp_hash' => Hash::make($otp),
            'expires_at' => now()->addMinutes(10),
        ]);

        return [$otpCode, $otp];
    }

    private function deliverOtp(string $otp, ?string $email, string $userName, string $context): string
    {
        if ($email) {
            try {
                Mail::to($email)->send(new OtpMail($otp, $userName));

                Log::info('OTP email sent', [
                    'context' => $context,
                    'email' => $email,
                ]);

                return 'email';
            } catch (\Throwable $e) {
                Log::error('Failed to send OTP email', [
                    'context' => $context,
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('OTP generated without email delivery', [
            'context' => $context,
            'otp' => $otp,
        ]);

        return 'manual';
    }

    private function findValidOtpCode(string $purpose, string $identifier, string $otp): ?OtpCode
    {
        $otpCode = OtpCode::where('purpose', $purpose)
            ->where('identifier', $this->normalizeIdentifier($identifier))
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (! $otpCode || ! Hash::check($otp, $otpCode->otp_hash)) {
            return null;
        }

        return $otpCode;
    }

    private function resolveUserByIdentifier(string $identifier): ?User
    {
        $identifier = $this->normalizeIdentifier($identifier);

        return User::query()
            ->where('email', $identifier)
            ->orWhere('mobile', $identifier)
            ->first();
    }

    private function normalizeIdentifier(string $identifier): string
    {
        $identifier = trim($identifier);

        return str_contains($identifier, '@')
            ? strtolower($identifier)
            : $identifier;
    }
}
