<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanyModule;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\CompanyContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SettingsController extends Controller
{
    // User Management
    public function users()
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $users = User::with(['role', 'company', 'branch'])->paginate(15);
        $roles = Role::all();
        $companies = Company::where('status', 'active')->get();
        $branches = Branch::where('status', 'active')->get();

        return view('settings.users.index', compact('users', 'roles', 'companies', 'branches'));
    }

    public function userStore(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'phone' => 'nullable|string|max:50',
            'role_id' => 'required|exists:roles,id',
            'company_id' => 'nullable|exists:companies,id',
            'branch_id' => 'nullable|exists:branches,id',
            'is_active' => 'nullable|boolean',
        ]);

        $data['password'] = Hash::make($data['password']);
        $data['is_active'] = $request->boolean('is_active', true);

        $user = User::create($data);
        AuditLog::log('settings', 'create_user', "Created ERP user {$user->email}");

        return back()->with('success', 'User account created successfully.');
    }

    public function userUpdate(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'role_id' => 'required|exists:roles,id',
            'company_id' => 'nullable|exists:companies,id',
            'branch_id' => 'nullable|exists:branches,id',
            'is_active' => 'nullable|boolean',
            'password' => 'nullable|min:6',
        ]);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $data['is_active'] = $request->boolean('is_active');
        $user->update($data);

        AuditLog::log('settings', 'update_user', "Updated ERP user {$user->email}");
        return back()->with('success', 'User account updated successfully.');
    }

    // Role & Permissions
    public function roles()
    {
        $roles = Role::withCount('permissions', 'users')->get();
        $permissions = Permission::all()->groupBy('module');

        return view('settings.roles.index', compact('roles', 'permissions'));
    }

    public function roleStore(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'required|string|max:100|unique:roles,slug',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role = Role::create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'is_system' => false,
        ]);

        if (!empty($data['permissions'])) {
            $role->permissions()->sync($data['permissions']);
        }

        AuditLog::log('settings', 'create_role', "Created custom role {$role->name}");
        return back()->with('success', 'Role created successfully.');
    }

    public function roleEdit(Role $role)
    {
        $permissions = Permission::all()->groupBy('module');
        $rolePermissions = $role->permissions->pluck('id')->toArray();

        return view('settings.roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    public function roleUpdate(Request $request, Role $role)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        $role->permissions()->sync($data['permissions'] ?? []);

        AuditLog::log('settings', 'update_role', "Updated role permissions for {$role->name}");
        return redirect()->route('settings.roles.index')->with('success', 'Role and permissions updated successfully.');
    }

    // Audit Logs
    public function auditLogs(Request $request)
    {
        $query = AuditLog::with('user');

        if ($request->filled('module')) {
            $query->where('module', $request->get('module'));
        }
        if ($request->filled('search')) {
            $s = $request->get('search');
            $query->where('description', 'like', "%{$s}%");
        }

        $logs = $query->latest()->paginate(25);
        return view('settings.audit_logs.index', compact('logs'));
    }

    // Company Module Access Configuration
    public function modules()
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $company = CompanyContextService::getActiveCompany();
        $modules = CompanyModule::where('company_id', $companyId)->get();

        return view('settings.modules.index', compact('company', 'modules'));
    }

    public function modulesUpdate(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $allModules = ['staff_hr', 'inventory', 'purchase', 'sales', 'accounts', 'production', 'vehicle_route', 'quality', 'crm'];
        $enabledModules = $request->input('modules', []);

        foreach ($allModules as $mod) {
            CompanyModule::updateOrCreate(
                ['company_id' => $companyId, 'module_key' => $mod],
                ['is_enabled' => in_array($mod, $enabledModules)]
            );
        }

        AuditLog::log('settings', 'update_modules', "Updated module configurations for active company");
        return back()->with('success', 'Company module access updated successfully.');
    }
}

