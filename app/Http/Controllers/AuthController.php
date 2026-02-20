<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        if ($request->ajax()) {
            $credentials = $request->only('username', 'password');
            $username = $credentials['username'];

            // Case-sensitive lookup for binary charset columns
            $user = User::whereRaw('username = BINARY ?', [$username])->first();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.'
                ], 401);
            }
            // Manual case-sensitive password check + login
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
}
