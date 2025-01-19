<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Mail\EmailVerification;
use App\Notifications\OTPNotification;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required_without:phone|email|unique:users,email',
            'phone' => 'required_without:email|string|unique:users,phone',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Generate verification token/OTP
        $verificationToken = Str::random(64);
        $otp = rand(100000, 999999);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'verification_token' => $verificationToken,
            'otp' => $otp,
        ]);

        // Send verification based on registration method
        if ($request->email) {
            Mail::to($user->email)->send(new EmailVerification($user));
        } else {
            $user->notify(new OTPNotification($otp));
        }

        return response()->json([
            'message' => 'Registration successful. Please verify your account.',
            'user' => $user
        ], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'identifier' => 'required|string', // This can be email or phone
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if identifier is email or phone
        $field = filter_var($request->identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        $user = User::where($field, $request->identifier)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        if (!$user->email_verified_at && !$user->phone_verified_at) {
            return response()->json([
                'message' => 'Please verify your account'
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token
        ]);
    }

    public function verifyEmail(Request $request)
    {
        $user = User::where('verification_token', $request->token)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Invalid verification token'
            ], 400);
        }

        $user->email_verified_at = now();
        $user->verification_token = null;
        $user->save();

        return response()->json([
            'message' => 'Email verified successfully'
        ]);
    }

    public function verifyOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'otp' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('phone', $request->phone)
            ->where('otp', $request->otp)
            ->first();

        if (!$user) {
            return response()->json([
                'message' => 'Invalid OTP'
            ], 400);
        }

        $user->phone_verified_at = now();
        $user->otp = null;
        $user->save();

        return response()->json([
            'message' => 'Phone verified successfully'
        ]);
    }

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }


    public function handleGoogleCallback(Request $request)
    {
        try {
            $idToken = $request->input('id_token');

            // Validate ID token with Google
            $validationUrl = "https://oauth2.googleapis.com/tokeninfo";
            $response = Http::get($validationUrl, [
                'id_token' => $idToken,
            ]);

            if ($response->failed()) {
                throw new Exception('Invalid ID Token');
            }

            $googleUser = $response->json();

            // Validate Google client ID
            if ($googleUser['aud'] !== env('GOOGLE_CLIENT_ID')) {
                throw new Exception('Invalid Client ID');
            }

            // Find or create user
            $user = User::firstOrCreate(
                ['email' => $googleUser['email']],
                [
                    'name' => $googleUser['name'] ?? 'Unknown',
                    'google_id' => $googleUser['sub'],
                    'password' => Hash::make(Str::random(24)),
                    'email_verified_at' => now(),
                ]
            );

            // Create access token
            $token = $user->createToken('google_auth')->plainTextToken;

            return response()->json([
                'message' => 'Google login successful',
                'user' => $user,
                'token' => $token,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function redirectToApple()
    {
        return Socialite::driver('apple')->redirect();
    }

    public function handleAppleCallback()
    {
        try {
            $appleUser = Socialite::driver('apple')->user();

            $user = User::where('apple_id', $appleUser->id)->first();

            if (!$user) {
                $user = User::create([
                    'name' => $appleUser->name ?? 'Apple User',
                    'email' => $appleUser->email,
                    'apple_id' => $appleUser->id,
                    'password' => Hash::make(Str::random(24)),
                    'email_verified_at' => now(),
                ]);
            }

            $token = $user->createToken('apple_auth')->plainTextToken;

            return response()->json([
                'message' => 'Apple login successful',
                'user' => $user,
                'token' => $token
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Apple authentication failed'
            ], 400);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }
}
