<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanyModule;
use App\Services\CompanyContextService;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index()
    {
        $companies = Company::withCount(['branches', 'departments'])->paginate(15);
        return view('companies.index', compact('companies'));
    }

    public function create()
    {
        return view('companies.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:companies,code',
            'business_type' => 'required|in:manufacturing,wholesale,distribution,trading,retail,service',
            'registration_no' => 'nullable|string|max:100',
            'gst_no' => 'nullable|string|max:50',
            'pan_no' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'website' => 'nullable|string|max:150',
            'address' => 'nullable|string',
            'currency' => 'required|string|max:10',
            'currency_symbol' => 'required|string|max:10',
            'financial_year_start' => 'required|date',
            'financial_year_end' => 'required|date|after:financial_year_start',
            'status' => 'required|in:active,inactive',
        ]);

        $company = Company::create($data);

        // Create Default Main Branch
        Branch::create([
            'company_id' => $company->id,
            'name' => 'Head Office / Main Branch',
            'code' => 'HQ-01',
            'address' => $company->address,
            'is_main_branch' => true,
            'status' => 'active',
        ]);

        // Default Modules
        $allModules = ['staff_hr', 'inventory', 'purchase', 'sales', 'accounts', 'production', 'vehicle_route', 'quality', 'crm'];
        foreach ($allModules as $mod) {
            $enabled = true;
            if ($company->business_type === 'service' && in_array($mod, ['inventory', 'production', 'quality', 'vehicle_route'])) {
                $enabled = false;
            }
            if ($company->business_type === 'retail' && in_array($mod, ['production', 'quality'])) {
                $enabled = false;
            }
            CompanyModule::create([
                'company_id' => $company->id,
                'module_key' => $mod,
                'is_enabled' => $enabled,
            ]);
        }

        AuditLog::log('company', 'create', "Created company: {$company->name} ({$company->code})");

        return redirect()->route('companies.index')->with('success', 'Company created successfully.');
    }

    public function edit(Company $company)
    {
        $company->load('modules', 'branches');
        return view('companies.edit', compact('company'));
    }

    public function update(Request $request, Company $company)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'business_type' => 'required|in:manufacturing,wholesale,distribution,trading,retail,service',
            'registration_no' => 'nullable|string|max:100',
            'gst_no' => 'nullable|string|max:50',
            'pan_no' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'website' => 'nullable|string|max:150',
            'address' => 'nullable|string',
            'currency' => 'required|string|max:10',
            'currency_symbol' => 'required|string|max:10',
            'financial_year_start' => 'required|date',
            'financial_year_end' => 'required|date|after:financial_year_start',
            'status' => 'required|in:active,inactive',
        ]);

        $company->update($data);

        // Update module configuration
        if ($request->has('modules')) {
            $submittedModules = $request->input('modules', []);
            $allModules = ['staff_hr', 'inventory', 'purchase', 'sales', 'accounts', 'production', 'vehicle_route', 'quality', 'crm'];
            foreach ($allModules as $mod) {
                CompanyModule::updateOrCreate(
                    ['company_id' => $company->id, 'module_key' => $mod],
                    ['is_enabled' => in_array($mod, $submittedModules)]
                );
            }
        }

        AuditLog::log('company', 'update', "Updated company: {$company->name}");

        return redirect()->route('companies.index')->with('success', 'Company updated successfully.');
    }

    public function switchCompany(Request $request)
    {
        $companyId = (int)$request->input('company_id');
        $company = Company::findOrFail($companyId);

        session(['active_company_id' => $company->id]);
        session()->forget('active_branch_id');

        $mainBranch = Branch::where('company_id', $company->id)->where('is_main_branch', true)->first();
        if ($mainBranch) {
            session(['active_branch_id' => $mainBranch->id]);
        }

        return back()->with('success', "Active company switched to '{$company->name}'.");
    }

    public function switchBranch(Request $request)
    {
        $branchId = (int)$request->input('branch_id');
        $branch = Branch::findOrFail($branchId);

        session(['active_branch_id' => $branch->id]);

        return back()->with('success', "Active branch switched to '{$branch->name}'.");
    }

    // Branch CRUD
    public function branchStore(Request $request, Company $company)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'contact_number' => 'nullable|string|max:50',
            'branch_manager' => 'nullable|string|max:100',
            'status' => 'required|in:active,inactive',
        ]);

        $data['company_id'] = $company->id;
        Branch::create($data);

        AuditLog::log('branch', 'create', "Created branch '{$data['name']}' for {$company->name}");

        return back()->with('success', 'Branch created successfully.');
    }
}

