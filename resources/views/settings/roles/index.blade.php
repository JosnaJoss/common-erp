@extends('layouts.app')

@section('title', 'Role & Permission Master')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Roles & Access Control</h4>
        <p class="text-muted small mb-0">Define granular screen-wise and module-wise permissions (view, add, edit, delete, approve, export)</p>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newRoleModal">
        <i class="bi bi-shield-plus me-1"></i> New Custom Role
    </button>
</div>

<!-- Roles Table -->
<div class="card shadow-sm border-0">
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
                                    <span class="badge bg-primary">Custom</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('settings.roles.edit', $role) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-sliders me-1"></i> Configure Matrix
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                No roles found.
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
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('settings.roles.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-shield-plus me-2 text-primary"></i> Create Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Role Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Quality Control Inspector" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Role Code / Slug <span class="text-danger">*</span></label>
                        <input type="text" name="slug" class="form-control" placeholder="e.g. qc_inspector" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Responsibilities of this role"></textarea>
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
@endsection

