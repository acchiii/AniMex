<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show()
    {
        return view('profile', [
            'user' => Auth::user(),
        ]);

    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
        ]);

        $user->name = $validated['name'];
        $user->username = $validated['username'] ?? $user->username;

        // Persist updates.
        // If this installation is missing Eloquent helpers in the IDE, this still works at runtime.
        $user->save();


        return redirect()->route('profile.show')->with('success', 'Profile updated.');
    }

    public function resendVerification(Request $request)
    {
        $user = Auth::user();

        // This project uses MustVerifyEmail, but some versions may not expose helper methods.
        // So we check email_verified_at directly.
        if ($user->email_verified_at) {
            return redirect()->route('profile.show')->with('success', 'Email is already verified.');
        }

        // If available, trigger Laravel email verification.
        if (method_exists($user, 'sendEmailVerificationNotification')) {
            $user->sendEmailVerificationNotification();
        } else {
            // Fallback: verification notification not supported in this build.
            return redirect()->route('profile.show')->with('success', 'Email verification is not supported on this installation.');
        }

        return redirect()->route('profile.show')->with('success', 'Verification email sent.');

    }
}


