<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckOwnershipMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $model, string $permission = null): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();
        
        // Admin can access everything
        if ($user->isAdmin()) {
            return $next($request);
        }

        // If specific permission is provided, check it first
        if ($permission && $user->hasPermission($permission)) {
            return $next($request);
        }

        // Get the model instance from route
        $modelInstance = null;
        $modelClass = 'App\\Models\\' . ucfirst($model);
        
        // Try to get model from route parameters
        $routeParams = $request->route()->parameters();
        
        foreach ($routeParams as $param) {
            if ($param instanceof $modelClass) {
                $modelInstance = $param;
                break;
            }
        }

        if (!$modelInstance) {
            // Try to find by id if provided
            if ($request->route($model)) {
                $modelInstance = $modelClass::find($request->route($model));
            }
        }

        if (!$modelInstance) {
            abort(404, 'Resource not found.');
        }

        // Check ownership
        if (property_exists($modelInstance, 'user_id') && $modelInstance->user_id !== $user->id) {
            abort(403, 'Access denied. You can only access your own records.');
        }

        return $next($request);
    }
}