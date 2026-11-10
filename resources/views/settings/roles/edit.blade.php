@extends('layouts.app')

@section('title', 'Configure Role Permissions - ' . $role->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Permission Matrix: {{ $role->name }}</h4>
        <p class="text-muted small mb-0">Manage granular permissions across all ERP modules: view, add, edit, delete, approve, and export</p>
    </div>
    <a href="{{ route('settings.roles.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Roles
    </a>
</div>

<form action="{{ route('settings.roles.update', $role) }}" method="POST">
    @csrf

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-shield me-1"></i> Role Details</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Role Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ $role->name }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Description</label>
                    <input type="text" name="description" class="form-control" value="{{ $role->description }}">
                </div>
            </div>
        </div>
    </div>

    <!-- Permissions Matrix -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-grid-3x3-gap me-1"></i> Screen & Module Permission Checkboxes</h6>
            <div>
                <button type="button" class="btn btn-sm btn-outline-primary" id="selectAllBtn">Select All</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAllBtn">Deselect All</button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 25%;">Module / Screen</th>
                            <th style="width: 75%;">Assigned Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($permissions as $moduleName => $permList)
                            <tr>
                                <td class="fw-bold text-dark">
                                    <i class="bi bi-folder-check text-primary me-2"></i> {{ ucwords(str_replace('_', ' ', $moduleName)) }}
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-3">
                                        @foreach($permList as $perm)
                                            <div class="form-check">
                                                <input class="form-check-input perm-checkbox" type="checkbox" name="permissions[]" value="{{ $perm->id }}" id="perm_{{ $perm->id }}" {{ in_array($perm->id, $rolePermissions) ? 'checked' : '' }}>
                                                <label class="form-check-label small" for="perm_{{ $perm->id }}">
                                                    <span class="badge bg-light text-dark border">
                                                        {{ $perm->action }}
                                                    </span>
                                                    <span class="text-muted" style="font-size: 0.8rem;">({{ $perm->name }})</span>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="text-end">
        <button type="submit" class="btn btn-primary px-4 py-2">
            <i class="bi bi-check2-circle me-1"></i> Update Role Permissions
        </button>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllBtn = document.getElementById('selectAllBtn');
    const deselectAllBtn = document.getElementById('deselectAllBtn');
    const checkboxes = document.querySelectorAll('.perm-checkbox');

    selectAllBtn.addEventListener('click', function() {
        checkboxes.forEach(cb => cb.checked = true);
    });

    deselectAllBtn.addEventListener('click', function() {
        checkboxes.forEach(cb => cb.checked = false);
    });
});
</script>
@endsection

