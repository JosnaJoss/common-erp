<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\CompanyContextService;

class SetCompanyContext
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check()) {
            // Check if user requested switching company
            if ($request->has('switch_company_id')) {
                $switchCompanyId = (int)$request->get('switch_company_id');
                // Ensure user has access or is super admin
                if (auth()->user()->isSuperAdmin() || auth()->user()->company_id == $switchCompanyId) {
                    session(['active_company_id' => $switchCompanyId]);
                    session()->forget('active_branch_id'); // reset branch on company switch
                }
            }

            // Check if user requested switching branch
            if ($request->has('switch_branch_id')) {
                $switchBranchId = (int)$request->get('switch_branch_id');
                session(['active_branch_id' => $switchBranchId]);
            }

            // Guarantee context is initialized
            CompanyContextService::getActiveCompanyId();
            CompanyContextService::getActiveBranchId();
        }

        return $next($request);
    }
}

