@extends('layouts.app')

@section('title', 'User Management - ' . ($activeCompany->name ?? 'Company Users'))

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1">
            <i class="bi bi-people text-primary me-2"></i>User Management
        </h4>
        <p class="text-muted small mb-0">
            Managing system user accounts, roles, and employee access for 
            <span class="badge bg-light text-dark border fw-semibold">
                <i class="bi bi-building me-1 text-primary"></i>{{ $activeCompany->name ?? 'Active Company' }}
            </span>
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#newRoleModal">
            <i class="bi bi-shield-plus me-1"></i> Create Role
        </button>
        <a href="{{ route('settings.roles.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-shield-lock me-1"></i> Manage Roles
        </a>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newUserModal">
            <i class="bi bi-person-plus me-1"></i> New User Account
        </button>
    </div>
</div>

<!-- Filter Bar -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('settings.users.index') }}" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Search by name, email, phone..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-4">
                <select name="role_id" class="form-select">
                    <option value="">All Company Roles</option>
                    @foreach($roles as $r)
                        <option value="{{ $r->id }}" {{ request('role_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                @if(request()->hasAny(['search', 'role_id']))
                    <a href="{{ route('settings.users.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Users Table -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>User Name & Email</th>
                        <th>Linked Employee</th>
                        <th>Role</th>
                        <th>Assigned Branch</th>
                        <th>Phone</th>
                        <th>Last Login</th>
                        <th>Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $u)
                        <tr>
                            <td>
                                <div class="fw-bold text-dark">{{ $u->name }}</div>
                                <small class="text-muted">{{ $u->email }}</small>
                            </td>
                            <td>
                                @if($u->staff)
                                    <div>
                                        <span class="badge bg-light text-primary border">
                                            <i class="bi bi-person-badge me-1"></i>{{ $u->staff->employee_id }}
                                        </span>
                                    </div>
                                    <small class="text-muted">{{ $u->staff->department->name ?? 'General' }}</small>
                                @else
                                    <span class="badge bg-light text-secondary border">System Account</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-primary">{{ $u->role->name ?? 'No Role' }}</span>
                            </td>
                            <td>{{ $u->branch->name ?? 'All Branches' }}</td>
                            <td>{{ $u->phone ?? '-' }}</td>
                            <td>
                                <small class="text-muted">{{ $u->last_login_at ? \Carbon\Carbon::parse($u->last_login_at)->diffForHumans() : 'Never' }}</small>
                            </td>
                            <td>
                                <span class="badge bg-{{ $u->is_active ? 'success' : 'danger' }}">
                                    {{ $u->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editUserModal{{ $u->id }}">
                                    <i class="bi bi-pencil me-1"></i> Edit
                                </button>
                            </td>
                        </tr>

                        <!-- Edit User Modal -->
                        <div class="modal fade" id="editUserModal{{ $u->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="{{ route('settings.users.update', $u) }}" method="POST">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title fw-bold">Edit User: {{ $u->name }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Full Name <span class="text-danger">*</span></label>
                                                <input type="text" name="name" class="form-control" value="{{ $u->name }}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Email (Read-Only)</label>
                                                <input type="email" class="form-control bg-light" value="{{ $u->email }}" readonly>
                                            </div>
                                            @if($u->staff)
                                                <div class="mb-3 p-2 bg-light border rounded small">
                                                    <i class="bi bi-person-badge text-primary me-1"></i>
                                                    <strong>Linked Employee:</strong> {{ $u->staff->full_name }} ({{ $u->staff->employee_id }})
                                                </div>
                                            @endif
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Role <span class="text-danger">*</span></label>
                                                <select name="role_id" class="form-select" required>
                                                    @foreach($roles as $r)
                                                        <option value="{{ $r->id }}" {{ $u->role_id == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                                                    @endforeach
                                                </select>
                                                <small class="text-muted">Showing roles for {{ $activeCompany->name }}</small>
                                            </div>
                                            @if($branches->count() > 0)
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Branch</label>
                                                    <select name="branch_id" class="form-select">
                                                        <option value="">All Branches</option>
                                                        @foreach($branches as $br)
                                                            <option value="{{ $br->id }}" {{ $u->branch_id == $br->id ? 'selected' : '' }}>{{ $br->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            @endif
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Phone</label>
                                                <input type="text" name="phone" class="form-control" value="{{ $u->phone }}">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Reset Password (leave empty to keep current)</label>
                                                <input type="password" name="password" class="form-control" placeholder="New Password">
                                            </div>
                                            <div class="form-check form-switch mb-2">
                                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="activeCheck{{ $u->id }}" {{ $u->is_active ? 'checked' : '' }}>
                                                <label class="form-check-label fw-bold" for="activeCheck{{ $u->id }}">User Account Active</label>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Update User</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-people fs-2 d-block mb-2 text-secondary"></i>
                                No users found for {{ $activeCompany->name ?? 'active company' }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($users->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $users->links() }}
        </div>
    @endif
</div>

<!-- New User Modal -->
<div class="modal fade" id="newUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('settings.users.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-person-plus me-2 text-primary"></i> Create System User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Current Company Notice (No Company Selection Field) -->
                    <div class="p-2 mb-3 bg-light border rounded small d-flex align-items-center">
                        <i class="bi bi-building text-primary fs-5 me-2"></i>
                        <div>
                            <div class="fw-bold text-dark">{{ $activeCompany->name }}</div>
                            <div class="text-muted" style="font-size: 0.8rem;">User will be automatically created under this active company</div>
                        </div>
                    </div>

                    <!-- Employee Selection Dropdown -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Employee <span class="text-danger">*</span></label>
                        @if($availableStaff->count() > 0)
                            <select name="staff_id" id="new_user_staff_id" class="form-select" required>
                                <option value="">-- Choose Existing Employee --</option>
                                @foreach($availableStaff as $st)
                                    <option value="{{ $st->id }}" 
                                            data-name="{{ $st->full_name }}" 
                                            data-email="{{ $st->email }}" 
                                            data-phone="{{ $st->phone }}" 
                                            data-branch="{{ $st->branch_id }}">
                                        {{ $st->full_name }} ({{ $st->employee_id }}) @if($st->department) - {{ $st->department->name }} @endif
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">User's full name is automatically set from the selected employee record</small>
                        @else
                            <div class="alert alert-warning py-2 px-3 small mb-0">
                                <i class="bi bi-exclamation-triangle me-1"></i> All employees in {{ $activeCompany->name }} already have system accounts. 
                                <a href="{{ route('staff.create') }}" class="alert-link">Add a new employee</a> in Staff Directory first.
                            </div>
                        @endif
                    </div>

                    <!-- Email Address -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Email Address / Login <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="new_user_email" class="form-control" placeholder="employee@company.com" required>
                    </div>

                    <!-- Password -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" placeholder="Min 6 characters" required>
                    </div>

                    <!-- Role Dropdown -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-bold mb-0">Role <span class="text-danger">*</span></label>
                            <a href="#" class="small text-primary text-decoration-none" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#newRoleModal">
                                <i class="bi bi-plus-circle me-1"></i>New Role
                            </a>
                        </div>
                        <select name="role_id" class="form-select" required>
                            <option value="">Select Role</option>
                            @foreach($roles as $r)
                                <option value="{{ $r->id }}">{{ $r->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Showing roles belonging to {{ $activeCompany->name }}</small>
                    </div>

                    <!-- Branch (Optional) -->
                    @if($branches->count() > 0)
                        <div class="mb-3">
                            <label class="form-label fw-bold">Assigned Branch</label>
                            <select name="branch_id" id="new_user_branch_id" class="form-select">
                                <option value="">All Branches</option>
                                @foreach($branches as $br)
                                    <option value="{{ $br->id }}">{{ $br->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <!-- Phone -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Phone Number</label>
                        <input type="text" name="phone" id="new_user_phone" class="form-control" placeholder="Mobile phone">
                    </div>

                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="newUserActive" checked>
                        <label class="form-check-label fw-bold" for="newUserActive">Account Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" @if($availableStaff->isEmpty()) disabled @endif>
                        <i class="bi bi-check-circle me-1"></i> Create User Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create Role Modal (Directly in User Management) -->
<div class="modal fade" id="newRoleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('settings.roles.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-shield-plus me-2 text-primary"></i> Create Role for {{ $activeCompany->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Role Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Sales Executive, Super Administrator, Store Keeper" required>
                            <small class="text-muted">Unique within this company (other companies can have same name)</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Description</label>
                            <input type="text" name="description" class="form-control" placeholder="Brief responsibilities of this role">
                        </div>
                    </div>

                    <!-- Permissions Matrix -->
                    <div class="border rounded p-3 bg-light">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-grid-3x3-gap me-1 text-primary"></i> Permissions Matrix</h6>
                            <div>
                                <button type="button" class="btn btn-sm btn-outline-primary py-0" id="selectAllRolePerms">Select All</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary py-0" id="deselectAllRolePerms">Deselect All</button>
                            </div>
                        </div>
                        <div style="max-height: 320px; overflow-y: auto;">
                            @foreach($permissions as $moduleName => $permList)
                                <div class="card border-0 mb-2 shadow-none">
                                    <div class="card-body p-2 bg-white rounded border">
                                        <div class="fw-bold text-dark small mb-1">
                                            <i class="bi bi-folder-check text-primary me-1"></i> {{ ucwords(str_replace('_', ' ', $moduleName)) }}
                                        </div>
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach($permList as $perm)
                                                <div class="form-check form-check-inline mb-1">
                                                    <input class="form-check-input role-perm-cb" type="checkbox" name="permissions[]" value="{{ $perm->id }}" id="m_perm_{{ $perm->id }}">
                                                    <label class="form-check-label small" for="m_perm_{{ $perm->id }}">
                                                        <span class="badge bg-light text-dark border">{{ $perm->action }}</span>
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i> Save Role</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-fill email, phone, and branch when employee is selected in New User Modal
    const staffSelect = document.getElementById('new_user_staff_id');
    const emailInput = document.getElementById('new_user_email');
    const phoneInput = document.getElementById('new_user_phone');
    const branchSelect = document.getElementById('new_user_branch_id');

    if (staffSelect) {
        staffSelect.addEventListener('change', function() {
            const selected = staffSelect.options[staffSelect.selectedIndex];
            if (selected && selected.value) {
                const email = selected.getAttribute('data-email');
                const phone = selected.getAttribute('data-phone');
                const branch = selected.getAttribute('data-branch');

                if (email && (!emailInput.value || emailInput.getAttribute('data-autofilled') === 'true')) {
                    emailInput.value = email;
                    emailInput.setAttribute('data-autofilled', 'true');
                }
                if (phone && (!phoneInput.value || phoneInput.getAttribute('data-autofilled') === 'true')) {
                    phoneInput.value = phone;
                    phoneInput.setAttribute('data-autofilled', 'true');
                }
                if (branch && branchSelect) {
                    branchSelect.value = branch;
                }
            }
        });
    }

    // Role permissions Select All / Deselect All
    const selectAllBtn = document.getElementById('selectAllRolePerms');
    const deselectAllBtn = document.getElementById('deselectAllRolePerms');
    const permCbs = document.querySelectorAll('.role-perm-cb');

    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function() {
            permCbs.forEach(cb => cb.checked = true);
        });
    }
    if (deselectAllBtn) {
        deselectAllBtn.addEventListener('click', function() {
            permCbs.forEach(cb => cb.checked = false);
        });
    }
});
</script>
@endsection
