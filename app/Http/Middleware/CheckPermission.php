<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $module, string $action = 'view')
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        if (!$user->hasPermission($module, $action)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthorized action for module: ' . $module], 403);
            }
            abort(403, "You do not have '{$action}' permission for {$module}.");
        }

        return $next($request);
    }
}

