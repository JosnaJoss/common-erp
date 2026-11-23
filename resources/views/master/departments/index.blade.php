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
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($departments as $dept)
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
                        <td class="text-end pe-4">
                            <button class="btn btn-sm btn-light border text-primary" data-bs-toggle="modal" data-bs-target="#editDeptModal{{ $dept->id }}" title="Edit Department">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form action="{{ route('master.departments.destroy', $dept) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this department?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-light border text-danger" title="Delete Department">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>

                    <!-- Edit Dept Modal -->
                    <div class="modal fade" id="editDeptModal{{ $dept->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <form action="{{ route('master.departments.update', $dept) }}" method="POST" class="modal-content">
                                @csrf
                                @method('PUT')
                                <div class="modal-header">
                                    <h5 class="modal-title fw-bold">Edit Department: {{ $dept->name }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body text-start">
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold">Department Name <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" value="{{ old('name', $dept->name) }}" required>
                                    </div>
                                    <div class="row g-2 mb-3">
                                        <div class="col-6">
                                            <label class="form-label small fw-semibold">Department Code <span class="text-danger">*</span></label>
                                            <input type="text" name="code" class="form-control" value="{{ old('code', $dept->code) }}" required>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-semibold">Branch</label>
                                            <select name="branch_id" class="form-select">
                                                <option value="">Select Branch</option>
                                                @foreach($branches as $b)
                                                <option value="{{ $b->id }}" {{ old('branch_id', $dept->branch_id) == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row g-2 mb-3">
                                        <div class="col-6">
                                            <label class="form-label small fw-semibold">Department Head</label>
                                            <input type="text" name="department_head" class="form-control" value="{{ old('department_head', $dept->department_head) }}">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-semibold">Responsible Staff</label>
                                            <input type="text" name="responsible_staff" class="form-control" value="{{ old('responsible_staff', $dept->responsible_staff) }}">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold">Status</label>
                                        <select name="status" class="form-select">
                                            <option value="active" {{ $dept->status === 'active' ? 'selected' : '' }}>Active</option>
                                            <option value="inactive" {{ $dept->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Update Department</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No departments found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($departments->hasPages())
    <div class="card-footer bg-white border-top py-3">
        {{ $departments->links() }}
    </div>
    @endif
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
