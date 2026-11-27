@extends('layouts.app')

@section('title', 'Roles & Permissions - ' . ($activeCompany->name ?? 'Company Roles'))

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1">
            <i class="bi bi-shield-lock text-primary me-2"></i>Roles & Access Control
        </h4>
        <p class="text-muted small mb-0">
            Define and manage company-specific roles and permissions for 
            <span class="badge bg-light text-dark border fw-semibold">
                <i class="bi bi-building me-1 text-primary"></i>{{ $activeCompany->name ?? 'Active Company' }}
            </span>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('settings.users.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-people me-1"></i> User Management
        </a>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newRoleModal">
            <i class="bi bi-shield-plus me-1"></i> New Custom Role
        </button>
    </div>
</div>

<!-- Roles Table -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Role Name</th>
                        <th>Slug Code</th>
                        <th>Description</th>
                        <th class="text-center">Permissions Assigned</th>
                        <th class="text-center">Active Users</th>
                        <th>Type</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roles as $role)
                        <tr>
                            <td>
                                <strong class="text-primary">{{ $role->name }}</strong>
                            </td>
                            <td><span class="badge bg-light text-dark border">{{ $role->slug }}</span></td>
                            <td><small class="text-muted">{{ $role->description ?? '-' }}</small></td>
                            <td class="text-center">
                                <span class="badge bg-info text-dark">{{ $role->permissions_count }} Permissions</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary">{{ $role->users_count }} Users</span>
                            </td>
                            <td>
                                @if($role->is_system)
                                    <span class="badge bg-dark">System Built-In</span>
                                @else
                                    <span class="badge bg-success">Company Custom</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="{{ route('settings.roles.edit', $role) }}" class="btn btn-sm btn-outline-primary" title="Configure Permission Matrix">
                                        <i class="bi bi-sliders me-1"></i> Configure Matrix
                                    </a>
                                    @if(!$role->is_system && $role->users_count == 0)
                                        <form action="{{ route('settings.roles.destroy', $role) }}" method="POST" onsubmit="return confirm('Delete role \'{{ $role->name }}\'?');" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Role">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <i class="bi bi-shield-x fs-2 d-block mb-2 text-secondary"></i>
                                No roles configured for {{ $activeCompany->name }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- New Role Modal -->
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
                            <input type="text" name="name" class="form-control" placeholder="e.g. Sales Executive, Super Administrator, Quality Control" required>
                            <small class="text-muted">Role names are unique within {{ $activeCompany->name }}</small>
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
                                                    <input class="form-check-input role-perm-cb" type="checkbox" name="permissions[]" value="{{ $perm->id }}" id="role_perm_{{ $perm->id }}">
                                                    <label class="form-check-label small" for="role_perm_{{ $perm->id }}">
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
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i> Create Role</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
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
