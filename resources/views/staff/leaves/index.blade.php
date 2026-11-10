@extends('layouts.app')

@section('title', 'Leave Management')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Staff Leave Management</h4>
        <p class="text-muted small mb-0">Leave applications, approval workflows, and leave history.</p>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newLeaveModal">
        <i class="bi bi-calendar-plus me-1"></i> Apply for Leave
    </button>
</div>

<div class="card card-custom">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Employee</th>
                        <th>Type</th>
                        <th>Duration & Dates</th>
                        <th>Days</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leaves as $leave)
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold">{{ $leave->staff->full_name ?? 'N/A' }}</div>
                            <small class="text-muted">{{ $leave->staff->employee_id ?? '' }}</small>
                        </td>
                        <td>
                            <span class="badge bg-light text-primary border">{{ $leave->leave_type }}</span>
                        </td>
                        <td class="small">
                            {{ $leave->start_date ? $leave->start_date->format('d M Y') : '' }} to 
                            {{ $leave->end_date ? $leave->end_date->format('d M Y') : '' }}
                        </td>
                        <td class="fw-bold">{{ $leave->number_of_days }} days</td>
                        <td class="small text-muted">{{ $leave->reason }}</td>
                        <td>
                            <span class="badge bg-{{ $leave->status === 'approved' ? 'success' : ($leave->status === 'rejected' ? 'danger' : 'warning text-dark') }}">
                                {{ ucfirst($leave->status) }}
                            </span>
                        </td>
                        <td class="text-end pe-4">
                            @if($leave->status === 'pending')
                            <form action="{{ route('staff.leaves.status', $leave) }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="status" value="approved">
                                <button type="submit" class="btn btn-sm btn-success" title="Approve">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                            </form>
                            <form action="{{ route('staff.leaves.status', $leave) }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="status" value="rejected">
                                <button type="submit" class="btn btn-sm btn-danger" title="Reject">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </form>
                            @else
                            <small class="text-muted">Reviewed</small>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center py-4 text-muted">No leave applications found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Apply Leave Modal -->
<div class="modal fade" id="newLeaveModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('staff.leaves.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Submit Leave Application</h5>
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
                    <label class="form-label small fw-semibold">Leave Type <span class="text-danger">*</span></label>
                    <select name="leave_type" class="form-select" required>
                        <option value="Casual">Casual Leave</option>
                        <option value="Sick">Sick Leave</option>
                        <option value="Earned">Earned / Privilege Leave</option>
                        <option value="Maternity">Maternity Leave</option>
                        <option value="Unpaid">Unpaid Leave</option>
                    </select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold">End Date <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" class="form-control" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Reason for Leave <span class="text-danger">*</span></label>
                    <textarea name="reason" class="form-control" rows="2" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Submit Application</button>
            </div>
        </form>
    </div>
</div>
@endsection

