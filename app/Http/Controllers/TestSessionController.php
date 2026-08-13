<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;

class TestSessionController extends Controller
{
    public function testSession()
    {
        return response()->json([
            'session_id' => Session::getId(),
            'session_data' => [
                'started_at' => Session::get('session_started_at'),
                'expires_at' => Session::get('session_expires_at'),
                'last_activity' => Session::get('last_activity'),
                'user_id' => Session::get('user_id'),
                'user_email' => Session::get('user_email'),
                'login_ip' => Session::get('login_ip'),
                'secure' => Session::get('session_secure')
            ],
            'config' => [
                'driver' => config('session.driver'),
                'lifetime' => config('session.lifetime'),
                'encrypt' => config('session.encrypt'),
                'expire_on_close' => config('session.expire_on_close'),
            ],
            'current_time' => Carbon::now()->toDateTimeString(),
            'authenticated' => auth()->check(),
            'user' => auth()->user()
        ]);
    }
}