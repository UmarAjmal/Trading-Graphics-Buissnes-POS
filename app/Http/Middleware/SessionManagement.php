<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Response;

class SessionManagement
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip session management for guest routes
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        $now = Carbon::now();
        
        // Check if this is a fresh login
        if (!Session::has('session_started_at')) {
            // Initialize session tracking
            Session::put([
                'session_started_at' => $now->toDateTimeString(),
                'last_activity' => $now->toDateTimeString(),
                'user_id' => $user->id,
                'user_email' => $user->email,
                'session_expires_at' => $now->addMinutes(config('session.lifetime'))->toDateTimeString(),
                'session_secure' => true,
                'login_ip' => $request->ip(),
                'login_user_agent' => $request->userAgent()
            ]);
            
            \Log::info('New session started', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
                'started_at' => $now->toDateTimeString()
            ]);
        }

        // Validate session integrity
        if (!$this->validateSessionIntegrity($request)) {
            return $this->terminateSession($request, 'Session integrity validation failed');
        }

        // Check session expiration
        if ($this->isSessionExpired()) {
            return $this->terminateSession($request, 'Session expired');
        }

        // Check for suspicious activity
        if ($this->detectSuspiciousActivity($request)) {
            return $this->terminateSession($request, 'Suspicious activity detected');
        }

        // Update last activity
        $this->updateLastActivity();

        // Extend session if more than half the time has passed
        if ($this->shouldExtendSession()) {
            $this->extendSession();
        }

        return $next($request);
    }

    /**
     * Validate session integrity
     */
    private function validateSessionIntegrity(Request $request): bool
    {
        // Check if session data is consistent
        if (!Session::has('user_id') || Session::get('user_id') != Auth::id()) {
            return false;
        }

        // Check if user still exists and is active
        $user = Auth::user();
        if (!$user || !$user->email) {
            return false;
        }

        // Verify session email matches current user
        if (Session::get('user_email') !== $user->email) {
            return false;
        }

        return true;
    }

    /**
     * Check if session has expired
     */
    private function isSessionExpired(): bool
    {
        $expiresAt = Session::get('session_expires_at');
        
        if (!$expiresAt) {
            return true;
        }

        return Carbon::now()->isAfter(Carbon::parse($expiresAt));
    }

    /**
     * Detect suspicious activity
     */
    private function detectSuspiciousActivity(Request $request): bool
    {
        // Check for IP address changes (optional - can be disabled for mobile users)
        $originalIp = Session::get('login_ip');
        $currentIp = $request->ip();
        
        // For now, we'll allow IP changes but log them
        if ($originalIp && $originalIp !== $currentIp) {
            \Log::warning('IP address changed during session', [
                'user_id' => Auth::id(),
                'original_ip' => $originalIp,
                'current_ip' => $currentIp,
                'session_id' => Session::getId()
            ]);
            // Update IP in session
            Session::put('login_ip', $currentIp);
        }

        // Check for user agent changes (could indicate session hijacking)
        $originalUserAgent = Session::get('login_user_agent');
        $currentUserAgent = $request->userAgent();
        
        if ($originalUserAgent && $originalUserAgent !== $currentUserAgent) {
            \Log::warning('User agent changed during session', [
                'user_id' => Auth::id(),
                'original_ua' => $originalUserAgent,
                'current_ua' => $currentUserAgent,
                'session_id' => Session::getId()
            ]);
            // This could be suspicious, but browsers can change user agents
            // For now, just log it
        }

        return false; // No suspicious activity detected
    }

    /**
     * Update last activity timestamp
     */
    private function updateLastActivity(): void
    {
        Session::put('last_activity', Carbon::now()->toDateTimeString());
    }

    /**
     * Check if session should be extended
     */
    private function shouldExtendSession(): bool
    {
        $lastActivity = Session::get('last_activity');
        $sessionLifetime = config('session.lifetime', 60);
        
        if (!$lastActivity) {
            return true;
        }

        $lastActivityTime = Carbon::parse($lastActivity);
        $halfLifetime = $sessionLifetime / 2;
        
        return Carbon::now()->diffInMinutes($lastActivityTime) > $halfLifetime;
    }

    /**
     * Extend session expiration
     */
    private function extendSession(): void
    {
        $newExpiration = Carbon::now()->addMinutes(config('session.lifetime'))->toDateTimeString();
        Session::put('session_expires_at', $newExpiration);
        
        \Log::info('Session extended', [
            'user_id' => Auth::id(),
            'new_expiration' => $newExpiration,
            'session_id' => Session::getId()
        ]);
    }

    /**
     * Terminate session and redirect to login
     */
    private function terminateSession(Request $request, string $reason): Response
    {
        $userId = Auth::id();
        $sessionId = Session::getId();
        
        \Log::warning('Session terminated', [
            'user_id' => $userId,
            'reason' => $reason,
            'session_id' => $sessionId,
            'ip' => $request->ip()
        ]);

        // Clear all session data
        Session::flush();
        Session::regenerate();
        
        // Logout the user
        Auth::logout();

        // Redirect based on request type
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'error' => 'Session expired',
                'message' => $reason,
                'redirect' => route('login')
            ], 401);
        }

        return redirect()->route('login')->with([
            'error' => 'Your session has expired. Please login again.',
            'reason' => $reason
        ]);
    }
}