@extends('layouts.app')

@section('title', 'Staff Directory')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Staff & Employee Registry</h4>
        <p class="text-muted small mb-0">Employment records, statutory benefits (PF / ESI), bank accounts, and licenses.</p>
    </div>
    <a href="{{ route('staff.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-person-plus me-1"></i> Register New Staff
    </a>
</div>

<!-- FILTERS -->
<div class="card card-custom mb-4">
    <div class="card-body p-3">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search staff name, ID, phone, or designation..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="department_id" class="form-select form-select-sm">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="probation" {{ request('status') == 'probation' ? 'selected' : '' }}>Probation</option>
                    <option value="resigned" {{ request('status') == 'resigned' ? 'selected' : '' }}>Resigned</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-search me-1"></i> Search</button>
                <a href="{{ route('staff.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card card-custom">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Employee</th>
                        <th>Department & Role</th>
                        <th>Contact</th>
                        <th>Employment & Salary</th>
                        <th>Statutory (PF / ESI)</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($staffMembers as $staff)
                    <tr>
                        <td class="ps-4">
                            <a href="{{ route('staff.show', $staff) }}" class="fw-bold text-dark text-decoration-none">
                                {{ $staff->full_name }}
                            </a>
                            <div><span class="badge bg-light text-primary border" style="font-size: 0.75rem;">{{ $staff->employee_id }}</span></div>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $staff->designation ?? 'N/A' }}</div>
                            <small class="text-muted">{{ $staff->department->name ?? 'General' }}</small>
                        </td>
                        <td class="small">
                            <div><i class="bi bi-telephone me-1 text-muted"></i>{{ $staff->phone ?? 'N/A' }}</div>
                            <div class="text-muted"><i class="bi bi-envelope me-1"></i>{{ $staff->email ?? 'N/A' }}</div>
                        </td>
                        <td class="small">
                            <span class="badge bg-secondary-subtle text-secondary text-capitalize mb-1">{{ $staff->employee_type }}</span>
                            <div class="fw-bold text-dark">₹{{ number_format($staff->salary_amount, 2) }}</div>
                        </td>
                        <td class="small">
                            <span class="badge bg-{{ $staff->pf_applicable ? 'success' : 'light text-muted border' }}">
                                PF {{ $staff->pf_applicable ? 'Yes' : 'No' }}
                            </span>
                            <span class="badge bg-{{ $staff->esi_applicable ? 'info text-dark' : 'light text-muted border' }}">
                                ESI {{ $staff->esi_applicable ? 'Yes' : 'No' }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-{{ $staff->status === 'active' ? 'success' : ($staff->status === 'probation' ? 'warning text-dark' : 'secondary') }}">
                                {{ ucfirst($staff->status) }}
                            </span>
                        </td>
                        <td class="text-end pe-4">
                            <a href="{{ route('staff.show', $staff) }}" class="btn btn-sm btn-light border" title="Staff Profile">
                                <i class="bi bi-person-lines-fill"></i>
                            </a>
                            <a href="{{ route('staff.edit', $staff) }}" class="btn btn-sm btn-light border" title="Edit Staff">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center py-4 text-muted">No staff members found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($staffMembers->hasPages())
    <div class="card-footer bg-white border-top py-3">
        {{ $staffMembers->links() }}
    </div>
    @endif
</div>
@endsection

