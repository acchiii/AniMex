<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $key = 'login:' . $request->ip();
        
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Too many login attempts. Try again in ' . ceil($seconds/60) . ' minutes.'], 429);
            }
            return back()->with('error', 'Too many login attempts. Please try again in ' . ceil($seconds/60) . ' minutes.');
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::clear($key);
            $request->session()->regenerate();
            
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Logged in successfully.']);
            }
            return redirect()->intended(route('home'))->with('success', 'Welcome back!');
        }

        RateLimiter::hit($key, 60);

        if ($request->expectsJson()) {
            return response()->json(['error' => 'The provided credentials do not match our records.', 'message' => 'Invalid credentials'], 422);
        }
        
        throw ValidationException::withMessages([
            'email' => ['The provided credentials do not match our records.'],
        ]);
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('home')->with('success', 'You have been logged out.');
    }
}
