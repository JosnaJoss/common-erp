@extends('layouts.app')

@section('title', 'Staff Overtime')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Overtime Tracking & Approval</h4>
        <p class="text-muted small mb-0">Record and approve overtime hours and computed payouts.</p>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newOvertimeModal">
        <i class="bi bi-plus-lg me-1"></i> Record Overtime
    </button>
</div>

<div class="card card-custom">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Employee</th>
                        <th>Date</th>
                        <th>Overtime Hours</th>
                        <th>Rate / Hour</th>
                        <th class="text-end">Total Amount</th>
                        <th>Approval Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($overtimes as $ot)
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold">{{ $ot->staff->full_name ?? 'N/A' }}</div>
                            <small class="text-muted">{{ $ot->staff->employee_id ?? '' }}</small>
                        </td>
                        <td>{{ $ot->date ? $ot->date->format('d M Y') : 'N/A' }}</td>
                        <td><span class="badge bg-light text-primary border">{{ $ot->overtime_hours }} hrs</span></td>
                        <td>₹{{ number_format($ot->overtime_rate, 2) }}</td>
                        <td class="text-end fw-bold text-success">₹{{ number_format($ot->total_amount, 2) }}</td>
                        <td>
                            <span class="badge bg-{{ $ot->approval_status === 'approved' ? 'success' : 'warning text-dark' }}">
                                {{ ucfirst($ot->approval_status) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center py-4 text-muted">No overtime records logged yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Overtime Modal -->
<div class="modal fade" id="newOvertimeModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('staff.overtime.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Record Overtime</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Staff Member <span class="text-danger">*</span></label>
                    <select name="staff_id" class="form-select" required>
                        <option value="">Select Staff</option>
                        @foreach($staffMembers as $s)
                        <option value="{{ $s->id }}">{{ $s->full_name }} ({{ $s->employee_id }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Date <span class="text-danger">*</span></label>
                    <input type="date" name="date" class="form-control" value="{{ now()->toDateString() }}" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Overtime Hours <span class="text-danger">*</span></label>
                        <input type="number" step="0.5" name="overtime_hours" class="form-control" required placeholder="e.g. 2.5">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Rate per Hour (₹) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="overtime_rate" class="form-control" required placeholder="e.g. 150">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Overtime</button>
            </div>
        </form>
    </div>
</div>
@endsection

