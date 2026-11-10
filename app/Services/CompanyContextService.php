<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Branch;
use App\Models\CompanyModule;

class CompanyContextService
{
    public static function getActiveCompanyId(): ?int
    {
        if (session()->has('active_company_id')) {
            return (int) session('active_company_id');
        }

        if (auth()->check() && auth()->user()->company_id) {
            session(['active_company_id' => auth()->user()->company_id]);
            return (int) auth()->user()->company_id;
        }

        $first = Company::where('status', 'active')->first();
        if ($first) {
            session(['active_company_id' => $first->id]);
            return $first->id;
        }

        return null;
    }

    public static function getActiveBranchId(): ?int
    {
        if (session()->has('active_branch_id')) {
            return (int) session('active_branch_id');
        }

        if (auth()->check() && auth()->user()->branch_id) {
            session(['active_branch_id' => auth()->user()->branch_id]);
            return (int) auth()->user()->branch_id;
        }

        $companyId = self::getActiveCompanyId();
        if ($companyId) {
            $branch = Branch::where('company_id', $companyId)->where('status', 'active')->first();
            if ($branch) {
                session(['active_branch_id' => $branch->id]);
                return $branch->id;
            }
        }

        return null;
    }

    public static function getActiveCompany(): ?Company
    {
        $id = self::getActiveCompanyId();
        return $id ? Company::find($id) : null;
    }

    public static function getActiveBranch(): ?Branch
    {
        $id = self::getActiveBranchId();
        return $id ? Branch::find($id) : null;
    }

    public static function isModuleEnabled(string $moduleKey): bool
    {
        $companyId = self::getActiveCompanyId();
        if (!$companyId) return true;

        $mod = CompanyModule::where('company_id', $companyId)
            ->where('module_key', $moduleKey)
            ->first();

        return $mod ? (bool)$mod->is_enabled : true;
    }
}

