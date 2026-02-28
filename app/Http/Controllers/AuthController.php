<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Mail\NotificationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;


class AuthController extends Controller
{
    public function login(Request $request)
    {
        if ($request->ajax()) {
            $validator = Validator::make($request->all(), [
                'username' => 'required|string|max:255',
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $credentials = $request->only('username', 'password');

            $user = User::whereRaw('username = BINARY ?', [$credentials['username']])->first();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.'
                ], 401);
            }

            if (Hash::check($credentials['password'], $user->password)) {
                Auth::login($user);
                return response()->json([
                    'success' => true,
                    'message' => 'Logged in successfully.',
                    'redirect_url' => route('home.index'),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Invalid password.'
            ], 401);
        }

        return view('auth.login');
    }



    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Check if request expects JSON (AJAX)
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully.',
                'redirect_url' => route('login')
            ]);
        }

        // Non-AJAX: redirect directly
        return redirect()->route('login');
    }

    public function forgot_password()
    {
        return view('auth.forgot-password');
    }

    public function change_password()  // GET - View
    {
        $email = session('reset_email');
        if (!$email) {
            return redirect()->route('forgot-password')
                ->with('error', 'Session expired. Please request new code.');
        }

        $user = User::where('email', $email)->first();
        if (!$user ||
            !$user->verification_code ||
            !$user->verification_expires_at ||
            now()->gt(Carbon::parse($user->verification_expires_at))) {  // Fixed line

            session()->forget('reset_email');
            return redirect()->route('forgot-password')
                ->with('error', 'Verification code expired. Please request new code.');
        }

        return view('auth.change-password');
    }


    public function verify_submit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);  // ← ADD 422 STATUS CODE
    }

        $user = User::where('email', trim($request->email))->first();
        $verificationCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->verification_code = $verificationCode;
        $user->verification_expires_at = now()->addMinutes(5);
        $user->save();

        session(['reset_email' => $user->email]);

        $data = [
            'subject' => 'Password Reset Verification Code',
            'template' => 'email.confirmation',
            'verification_code' => $verificationCode,
            'expires_at' => $user->verification_expires_at->format('M d, Y g:i A'),
            'user_name' => $user->username ?? $user->name ?? 'User',
        ];

        try {
            Mail::to($user->email)->send(new NotificationMail($data));
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to send email. Please try again.'
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Verification code sent to your email.',
            'redirect' => route('change-password')
        ]);
    }

    public function change_password_submit(Request $request)  // POST - Submit
    {
        $validator = Validator::make($request->all(), [
            'verification_code' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);  // Add 422 status for validation errors
        }

        $email = session('reset_email');
        if (!$email) {
            return response()->json([
                'status' => false,
                'message' => 'Session expired. Please request new code.'
            ], 400);
        }

        $user = User::where('email', $email)
            ->where('verification_code', $request->verification_code)
            ->where('verification_expires_at', '>', now())
            ->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid or expired verification code.'
            ], 400);
        }

        $user->password = Hash::make($request->password);
        $user->verification_code = null;
        $user->verification_expires_at = null;
        $user->save();

        session()->forget('reset_email');

        return response()->json([
            'status' => true,
            'message' => 'Password updated successfully!',
            'redirect' => route('login'),
        ]);
    }



}
