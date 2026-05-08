<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Rules\ReCaptcha;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AdminLoginController extends Controller
{
    public function show()
    {
        // Admin auth is handled via overlay modal.
        return redirect()->route('admin.dashboard');
    }

    public function login(Request $request)
    {
        $rules = [
            'email' => 'required|email',
            'password' => 'required|string',
        ];

        if (config('services.recaptcha.enabled')) {
            $rules['g-recaptcha-response'] = ['nullable', new ReCaptcha(0.5, 'admin_login')];
        }

        $credentials = $request->validate($rules);

        $key = 'admin_login:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            if ($request->expectsJson()) {
                return response()->json(['error' => 'Too many login attempts. Try again in ' . ceil($seconds / 60) . ' minutes.'], 429);
            }

            return back()->with('error', 'Too many login attempts. Try again in ' . ceil($seconds / 60) . ' minutes.')
                ->with('admin_login_error', 'Too many login attempts. Try again later.');
        }

        $authCredentials = [
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ];

        if (Auth::attempt($authCredentials, $request->boolean('remember'))) {
            RateLimiter::clear($key);
            $request->session()->regenerate();

            // Ensure only admins can access admin area.
            if (!Auth::user() || !Auth::user()->isAdmin()) {
                Auth::logout();
                RateLimiter::hit($key, 60);

                throw ValidationException::withMessages([
                    'email' => ['The provided credentials do not match our records.'],
                ])->errorBag('adminLogin');
            }

            return redirect()->intended(route('admin.dashboard'));
        }

        RateLimiter::hit($key, 60);

        if ($request->expectsJson()) {
            return response()->json(['error' => 'The provided credentials do not match our records.', 'message' => 'Invalid credentials'], 422);
        }

        throw ValidationException::withMessages([
            'email' => ['The provided credentials do not match our records.'],
        ])->errorBag('adminLogin');
    }
}


