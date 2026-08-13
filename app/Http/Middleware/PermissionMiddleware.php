<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permissions): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();
        $requiredPermissions = explode('|', $permissions);
        
        // Check if user has any of the required permissions
        $hasPermission = false;
        
        foreach ($requiredPermissions as $permission) {
            // Handle AND conditions within a single permission (using & separator)
            $andPermissions = explode('&', trim($permission));
            
            if (count($andPermissions) > 1) {
                // All permissions in the AND group must be satisfied
                if ($user->hasAllPermissions($andPermissions)) {
                    $hasPermission = true;
                    break;
                }
            } else {
                // Single permission check
                if ($user->hasPermission(trim($permission))) {
                    $hasPermission = true;
                    break;
                }
            }
        }

        if (!$hasPermission) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Access denied. Insufficient permissions.',
                    'required_permissions' => $requiredPermissions
                ], 403);
            }
            
            abort(403, 'Access denied. You do not have the required permissions to access this resource.');
        }

        return $next($request);
    }
}