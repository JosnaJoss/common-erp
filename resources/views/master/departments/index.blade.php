@extends('layouts.app')

@section('title', 'Departments')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Company Departments</h4>
        <p class="text-muted small mb-0">Organizational units for staff, requisitions, and operational management.</p>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newDeptModal">
        <i class="bi bi-plus-lg me-1"></i> Add Department
    </button>
</div>

<div class="card card-custom">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Department Name & Code</th>
                        <th>Branch</th>
                        <th>Department Head</th>
                        <th>Responsible Staff</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($departments as $dept)
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold">{{ $dept->name }}</div>
                            <span class="badge bg-light text-primary border">{{ $dept->code }}</span>
                        </td>
                        <td>{{ $dept->branch->name ?? 'Company Head Office' }}</td>
                        <td>{{ $dept->department_head ?? 'N/A' }}</td>
                        <td>{{ $dept->responsible_staff ?? 'N/A' }}</td>
                        <td>
                            <span class="badge bg-{{ $dept->status === 'active' ? 'success' : 'secondary' }}">
                                {{ ucfirst($dept->status) }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Dept Modal -->
<div class="modal fade" id="newDeptModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('master.departments.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Add Department</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Department Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Accounts & Finance">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Department Code <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control" required placeholder="e.g. DEPT-ACC">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Branch</label>
                        <select name="branch_id" class="form-select">
                            <option value="">Select Branch</option>
                            @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Department Head</label>
                        <input type="text" name="department_head" class="form-control">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Responsible Staff</label>
                        <input type="text" name="responsible_staff" class="form-control">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Department</button>
            </div>
        </form>
    </div>
</div>
@endsection

