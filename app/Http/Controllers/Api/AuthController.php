<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    // Send OTP
    public function sendOtp(Request $request)
    {
        $request->validate(['mobile' => 'required|string|exists:users,mobile']);

        $otp = rand(100000, 999999);
        $user = User::where('mobile', $request->mobile)->first();
        $user->update([
            'otp' => $otp,
            'otp_expires_at' => now()->addMinutes(5)
        ]);

        // Log OTP sending
        Log::info('📱 OTP Generated and Saved', [
            'mobile' => $request->mobile,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'otp' => $otp,
            'expires_in_minutes' => 5,
            'created_at' => now()->format('Y-m-d H:i:s'),
        ]);

        // Send OTP via email if email exists
        if ($user->email) {
            try {
                Mail::to($user->email)->send(new OtpMail($otp, $user->name));
                Log::info('✅ OTP Email Sent Successfully', [
                    'email' => $user->email,
                    'mobile' => $request->mobile,
                    'otp' => $otp,
                ]);
            } catch (\Exception $e) {
                Log::error('❌ Failed to Send OTP Email', [
                    'email' => $user->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // In real project, send SMS here
        return response()->json(['message' => 'OTP sent successfully', 'otp' => $otp]);
    }

    // Verify OTP & Login
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'mobile' => 'required|string|exists:users,mobile',
            'otp' => 'required|string|size:6'
        ]);

        $user = User::where('mobile', $request->mobile)
            ->where('otp', $request->otp)
            ->where('otp_expires_at', '>', now())
            ->first();

        if (!$user) {
            Log::warning('⚠️ Failed OTP Verification Attempt', [
                'mobile' => $request->mobile,
                'otp_provided' => $request->otp,
                'attempted_at' => now()->format('Y-m-d H:i:s'),
            ]);
            return response()->json(['message' => 'Invalid or expired OTP'], 401);
        }

        $user->update(['otp' => null, 'otp_expires_at' => null]);
        $token = $user->createToken('auth_token')->plainTextToken;

        Log::info('✅ OTP Verification Successful', [
            'mobile' => $request->mobile,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'verified_at' => now()->format('Y-m-d H:i:s'),
        ]);

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token
        ]);
    }

    // Get Profile
    public function profile(Request $request)
    {
        return response()->json($request->user());
    }

    // Update Profile
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $user->update($request->only(['name', 'email', 'address']));
        return response()->json(['message' => 'Profile updated', 'user' => $user]);
    }

}