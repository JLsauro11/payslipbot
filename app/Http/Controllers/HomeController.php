<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class HomeController extends Controller
{
    public function index(Request $request) {
        if ($request->ajax() || $request->wantsJson() || $request->has('ajax')) {
            $user = Auth::user();
            return response()->json([
                'username' => $user->username ?? $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar ? 'assets/images/avatar/' . $user->avatar : null
            ]);
        }

        return view('dashboard.index');
    }

    public function updateAccount(Request $request) {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)], // ✅ Email validation
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'current_password' => 'exclude_if:password,null|required_with:password',
            'password' => 'exclude_if:current_password,null|min:8|confirmed'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $data = $request->only(['username', 'email']); // ✅ Include email

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            if ($user->avatar && file_exists(public_path('assets/images/avatar/' . $user->avatar))) {
                unlink(public_path('assets/images/avatar/' . $user->avatar));
            }

            $avatarName = time() . '_' . $request->file('avatar')->getClientOriginalName();
            $request->file('avatar')->move(public_path('assets/images/avatar/'), $avatarName);
            $data['avatar'] = $avatarName;
        }

        // Handle password update
        if ($request->filled('current_password') && $request->filled('password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect.'
                ], 422);
            }
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Account updated successfully!',
            'user' => [
                'username' => $user->username,
                'email' => $user->email,
                'avatar' => $user->avatar ? asset('assets/images/avatar/' . $user->avatar) : null
            ]
        ]);
    }


}
