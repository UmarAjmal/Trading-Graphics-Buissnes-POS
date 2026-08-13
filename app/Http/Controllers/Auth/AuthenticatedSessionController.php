<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        // Regenerate session ID for security
        $request->session()->regenerate();

        // Initialize session tracking data
        $user = Auth::user();
        $now = now();
        
        // Clear any existing session data
        $request->session()->forget([
            'session_started_at', 'last_activity', 'user_id', 
            'user_email', 'session_expires_at', 'session_secure',
            'login_ip', 'login_user_agent'
        ]);

        // Set new session data
        $request->session()->put([
            'session_started_at' => $now->toDateTimeString(),
            'last_activity' => $now->toDateTimeString(),
            'user_id' => $user->id,
            'user_email' => $user->email,
            'session_expires_at' => $now->addMinutes(config('session.lifetime', 60))->toDateTimeString(),
            'session_secure' => true,
            'login_ip' => $request->ip(),
            'login_user_agent' => $request->userAgent(),
            'login_timestamp' => $now->toDateTimeString()
        ]);

        // Log successful login
        \Log::info('User logged in successfully', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => $request->session()->getId(),
            'timestamp' => $now->toDateTimeString()
        ]);

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $sessionId = $request->session()->getId();

        // Log logout activity
        if ($user) {
            \Log::info('User logged out', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
                'session_id' => $sessionId,
                'timestamp' => now()->toDateTimeString()
            ]);
        }

        // Clear authentication
        Auth::guard('web')->logout();

        // Clear all session data
        $request->session()->invalidate();

        // Regenerate CSRF token
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'You have been logged out successfully.');
    }
}