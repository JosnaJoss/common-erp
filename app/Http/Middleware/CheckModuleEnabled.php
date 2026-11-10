<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\CompanyContextService;

class CheckModuleEnabled
{
    public function handle(Request $request, Closure $next, string $moduleKey)
    {
        if (!CompanyContextService::isModuleEnabled($moduleKey)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => "Module '{$moduleKey}' is disabled for the current company."], 403);
            }
            abort(403, "The '{$moduleKey}' module is not enabled for your company profile.");
        }

        return $next($request);
    }
}

