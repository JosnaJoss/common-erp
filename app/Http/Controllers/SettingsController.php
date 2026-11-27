<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanyModule;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Staff;
use App\Models\User;
use App\Services\CompanyContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SettingsController extends Controller
{
    // User Management
    public function users(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if (!$companyId) {
            $companyId = Company::where('status', 'active')->value('id');
            if ($companyId) {
                session(['active_company_id' => $companyId]);
            }
        }
        $activeCompany = Company::find($companyId);

        $query = User::where('company_id', $companyId)->with(['role', 'company', 'branch', 'staff']);

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role_id')) {
            $query->where('role_id', $request->get('role_id'));
        }

        $users = $query->latest()->paginate(15)->withQueryString();

        // Roles belonging strictly to this active company
        $roles = Role::where('company_id', $companyId)->orderBy('name')->get();

        // Branches for this active company
        $branches = Branch::where('company_id', $companyId)->where('status', 'active')->get();

        // Staff belonging to this company who do not already have an active system user
        $assignedStaffIds = User::where('company_id', $companyId)->whereNotNull('staff_id')->pluck('staff_id')->filter();
        $availableStaff = Staff::where('company_id', $companyId)
            ->whereNull('user_id')
            ->whereNotIn('id', $assignedStaffIds)
            ->with('department')
            ->orderBy('first_name')
            ->get();

        // Permissions grouped by module for the Create Role modal directly on the User Management screen
        $permissions = Permission::all()->groupBy('module');

        return view('settings.users.index', compact('users', 'roles', 'branches', 'availableStaff', 'activeCompany', 'permissions'));
    }

    public function userStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if (!$companyId) {
            abort(400, 'Active company context is required.');
        }

        $data = $request->validate([
            'staff_id' => [
                'required',
                'exists:staff,id',
                Rule::exists('staff', 'id')->where('company_id', $companyId),
            ],
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'phone' => 'nullable|string|max:50',
            'role_id' => [
                'required',
                Rule::exists('roles', 'id')->where('company_id', $companyId),
            ],
            'branch_id' => [
                'nullable',
                Rule::exists('branches', 'id')->where('company_id', $companyId),
            ],
            'is_active' => 'nullable|boolean',
        ], [
            'staff_id.required' => 'Please select an employee for this user account.',
            'staff_id.exists' => 'The selected employee does not belong to the active company.',
            'role_id.exists' => 'The selected role does not belong to the active company.',
            'branch_id.exists' => 'The selected branch does not belong to the active company.',
        ]);

        // Prevent assigning staff that already has a user account
        $staff = Staff::where('company_id', $companyId)->findOrFail($data['staff_id']);
        if ($staff->user_id || User::where('staff_id', $staff->id)->exists()) {
            throw ValidationException::withMessages([
                'staff_id' => 'The selected employee already has a system user account.',
            ]);
        }

        // Auto-extract full name from staff record
        $userName = trim($staff->first_name . ' ' . $staff->last_name);

        $user = User::create([
            'name' => $userName,
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'] ?? $staff->phone,
            'role_id' => $data['role_id'],
            'company_id' => $companyId,
            'branch_id' => $data['branch_id'] ?? $staff->branch_id,
            'staff_id' => $staff->id,
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Associate user_id on staff record
        $staff->update(['user_id' => $user->id]);

        // Associate in user_company_branches
        DB::table('user_company_branches')->insertOrIgnore([
            'user_id' => $user->id,
            'company_id' => $companyId,
            'branch_id' => $user->branch_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        AuditLog::log('settings', 'create_user', "Created ERP user {$user->email} for employee {$staff->employee_id} ({$userName})");

        return back()->with('success', "System user account for '{$userName}' created successfully.");
    }

    public function userUpdate(Request $request, User $user)
    {
        $companyId = CompanyContextService::getActiveCompanyId();

        // Enforce company boundary
        if ($user->company_id != $companyId) {
            abort(403, 'Unauthorized access to user from another company.');
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'role_id' => [
                'required',
                Rule::exists('roles', 'id')->where('company_id', $companyId),
            ],
            'branch_id' => [
                'nullable',
                Rule::exists('branches', 'id')->where('company_id', $companyId),
            ],
            'is_active' => 'nullable|boolean',
            'password' => 'nullable|min:6',
        ], [
            'role_id.exists' => 'The selected role does not belong to the active company.',
            'branch_id.exists' => 'The selected branch does not belong to the active company.',
        ]);

        $updateData = [
            'name' => $data['name'],
            'phone' => $data['phone'] ?? $user->phone,
            'role_id' => $data['role_id'],
            'branch_id' => $data['branch_id'] ?? $user->branch_id,
            'is_active' => $request->boolean('is_active'),
        ];

        if (!empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user->update($updateData);

        AuditLog::log('settings', 'update_user', "Updated ERP user {$user->email}");
        return back()->with('success', 'User account updated successfully.');
    }

    // Role & Permissions
    public function roles()
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if (!$companyId) {
            $companyId = Company::where('status', 'active')->value('id');
            if ($companyId) {
                session(['active_company_id' => $companyId]);
            }
        }
        $activeCompany = Company::find($companyId);
        $roles = Role::where('company_id', $companyId)->withCount('permissions', 'users')->get();
        $permissions = Permission::all()->groupBy('module');

        return view('settings.roles.index', compact('roles', 'permissions', 'activeCompany'));
    }

    public function roleStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if (!$companyId) {
            abort(400, 'Active company context is required.');
        }

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('roles', 'name')->where(function ($query) use ($companyId) {
                    return $query->where('company_id', $companyId);
                }),
            ],
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ], [
            'name.unique' => 'A role with this name already exists in this company.',
        ]);

        $baseSlug = Str::slug($data['name']);
        $slug = $baseSlug ?: 'role';
        $counter = 1;
        while (Role::where('company_id', $companyId)->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . (++$counter);
        }

        $role = Role::create([
            'company_id' => $companyId,
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'is_system' => false,
        ]);

        if (!empty($data['permissions'])) {
            $role->permissions()->sync($data['permissions']);
        }

        AuditLog::log('settings', 'create_role', "Created custom role {$role->name} for company #{$companyId}");
        return back()->with('success', "Role '{$role->name}' created successfully.");
    }

    public function roleEdit(Role $role)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($role->company_id && $role->company_id != $companyId) {
            abort(403, 'Unauthorized access to role belonging to another company.');
        }

        $permissions = Permission::all()->groupBy('module');
        $rolePermissions = $role->permissions->pluck('id')->toArray();

        return view('settings.roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    public function roleUpdate(Request $request, Role $role)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($role->company_id && $role->company_id != $companyId) {
            abort(403, 'Unauthorized access to role belonging to another company.');
        }

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('roles', 'name')->where(function ($query) use ($companyId) {
                    return $query->where('company_id', $companyId);
                })->ignore($role->id),
            ],
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ], [
            'name.unique' => 'A role with this name already exists in this company.',
        ]);

        $role->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        $role->permissions()->sync($data['permissions'] ?? []);

        AuditLog::log('settings', 'update_role', "Updated role permissions for {$role->name}");
        return redirect()->route('settings.roles.index')->with('success', 'Role and permissions updated successfully.');
    }

    public function roleDestroy(Role $role)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($role->company_id != $companyId) {
            abort(403, 'Unauthorized access to delete role belonging to another company.');
        }

        if ($role->is_system) {
            return back()->with('error', 'System built-in roles cannot be deleted.');
        }

        if ($role->users()->count() > 0) {
            return back()->with('error', "Cannot delete role '{$role->name}' because it currently has assigned users.");
        }

        $roleName = $role->name;
        $role->permissions()->detach();
        $role->delete();

        AuditLog::log('settings', 'delete_role', "Deleted custom role {$roleName}");
        return back()->with('success', "Role '{$roleName}' deleted successfully.");
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

