<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;

class SessionController extends Controller
{
    /**
     * Get current session information
     */
    public function getSessionInfo(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'authenticated' => false,
                'message' => 'Not authenticated'
            ], 401);
        }

        $user = Auth::user();
        $now = Carbon::now();
        $expiresAt = Session::get('session_expires_at');
        $startedAt = Session::get('session_started_at');
        $lastActivity = Session::get('last_activity');

        $sessionInfo = [
            'authenticated' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role ?? 'user'
            ],
            'session' => [
                'id' => Session::getId(),
                'started_at' => $startedAt,
                'expires_at' => $expiresAt,
                'last_activity' => $lastActivity,
                'lifetime_minutes' => config('session.lifetime', 60),
                'time_remaining_minutes' => $expiresAt ? max(0, $now->diffInMinutes(Carbon::parse($expiresAt), false)) : 0,
                'is_expired' => $expiresAt ? $now->isAfter(Carbon::parse($expiresAt)) : false,
                'auto_extend' => true
            ],
            'security' => [
                'login_ip' => Session::get('login_ip'),
                'current_ip' => $request->ip(),
                'secure' => Session::get('session_secure', false),
                'encrypted' => config('session.encrypt', false)
            ]
        ];

        return response()->json($sessionInfo);
    }

    /**
     * Extend current session
     */
    public function extendSession(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Not authenticated'
            ], 401);
        }

        $now = Carbon::now();
        $newExpiration = $now->addMinutes(config('session.lifetime', 60))->toDateTimeString();
        
        Session::put([
            'session_expires_at' => $newExpiration,
            'last_activity' => $now->toDateTimeString()
        ]);

        \Log::info('Session manually extended', [
            'user_id' => Auth::id(),
            'new_expiration' => $newExpiration,
            'session_id' => Session::getId()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Session extended successfully',
            'expires_at' => $newExpiration,
            'time_remaining_minutes' => config('session.lifetime', 60)
        ]);
    }

    /**
     * Check session validity (lightweight endpoint for frontend polling)
     */
    public function checkSession(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'valid' => false,
                'authenticated' => false
            ], 401);
        }

        $expiresAt = Session::get('session_expires_at');
        $isExpired = $expiresAt ? Carbon::now()->isAfter(Carbon::parse($expiresAt)) : true;

        if ($isExpired) {
            // Session is expired, force logout
            Auth::logout();
            Session::flush();
            
            return response()->json([
                'valid' => false,
                'authenticated' => false,
                'expired' => true,
                'message' => 'Session expired'
            ], 401);
        }

        // Update last activity
        Session::put('last_activity', Carbon::now()->toDateTimeString());

        return response()->json([
            'valid' => true,
            'authenticated' => true,
            'time_remaining_minutes' => Carbon::now()->diffInMinutes(Carbon::parse($expiresAt), false)
        ]);
    }

    /**
     * Get active sessions for current user (if using database sessions)
     */
    public function getActiveSessions(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'sessions' => [],
                'message' => 'Not authenticated'
            ], 401);
        }

        // Only available with database session driver
        if (config('session.driver') !== 'database') {
            return response()->json([
                'sessions' => [],
                'message' => 'Active sessions tracking requires database session driver'
            ]);
        }

        try {
            $sessions = \DB::table('sessions')
                ->where('user_id', Auth::id())
                ->get()
                ->map(function ($session) {
                    $payload = unserialize(base64_decode($session->payload));
                    return [
                        'id' => $session->id,
                        'ip_address' => $session->ip_address,
                        'user_agent' => $session->user_agent,
                        'last_activity' => Carbon::createFromTimestamp($session->last_activity)->toDateTimeString(),
                        'is_current' => $session->id === Session::getId(),
                        'login_time' => $payload['login_timestamp'] ?? null
                    ];
                });

            return response()->json([
                'sessions' => $sessions,
                'current_session_id' => Session::getId()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'sessions' => [],
                'message' => 'Could not retrieve active sessions'
            ], 500);
        }
    }
}