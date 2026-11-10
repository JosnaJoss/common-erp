@extends('layouts.app')

@section('title', 'User Management')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">User Management</h4>
        <p class="text-muted small mb-0">Create, configure, and manage system user access, company assignments, and roles</p>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newUserModal">
        <i class="bi bi-person-plus me-1"></i> New User Account
    </button>
</div>

<!-- Users Table -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>User Name & Email</th>
                        <th>Role</th>
                        <th>Assigned Company</th>
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
                                <span class="badge bg-primary">{{ $u->role->name ?? 'User' }}</span>
                            </td>
                            <td>
                                @if($u->company)
                                    <i class="bi bi-building me-1 text-muted"></i> {{ $u->company->name }}
                                @else
                                    <span class="badge bg-dark">All Companies (Super Admin)</span>
                                @endif
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
                                                <input type="email" class="form-control" value="{{ $u->email }}" readonly>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Role <span class="text-danger">*</span></label>
                                                <select name="role_id" class="form-select" required>
                                                    @foreach($roles as $r)
                                                        <option value="{{ $r->id }}" {{ $u->role_id == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Assigned Company</label>
                                                <select name="company_id" class="form-select">
                                                    <option value="">All Companies (Super Admin)</option>
                                                    @foreach($companies as $comp)
                                                        <option value="{{ $comp->id }}" {{ $u->company_id == $comp->id ? 'selected' : '' }}>{{ $comp->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
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
                                No users found.
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
                    <div class="mb-3">
                        <label class="form-label fw-bold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. John Doe" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" placeholder="john@erp.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" placeholder="Min 6 characters" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Role <span class="text-danger">*</span></label>
                        <select name="role_id" class="form-select" required>
                            <option value="">Select Role</option>
                            @foreach($roles as $r)
                                <option value="{{ $r->id }}">{{ $r->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Assign Company</label>
                        <select name="company_id" class="form-select">
                            <option value="">All Companies (Super Admin)</option>
                            @foreach($companies as $comp)
                                <option value="{{ $comp->id }}" {{ session('active_company_id') == $comp->id ? 'selected' : '' }}>{{ $comp->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Phone</label>
                        <input type="text" name="phone" class="form-control" placeholder="Phone number">
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="newUserActive" checked>
                        <label class="form-check-label fw-bold" for="newUserActive">Account Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i> Create Account</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

