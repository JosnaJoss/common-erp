@extends('layouts.app')

@section('title', 'Shifts & Rosters')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Shift Management</h4>
        <p class="text-muted small mb-0">Configure work shifts, start/end schedules, and break durations.</p>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newShiftModal">
        <i class="bi bi-plus-lg me-1"></i> Add Shift
    </button>
</div>

<div class="card card-custom">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Shift Name</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Break Duration</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($shifts as $shift)
                    <tr>
                        <td class="ps-4 fw-bold text-dark">{{ $shift->shift_name }}</td>
                        <td>{{ \Carbon\Carbon::parse($shift->start_time)->format('h:i A') }}</td>
                        <td>{{ \Carbon\Carbon::parse($shift->end_time)->format('h:i A') }}</td>
                        <td>{{ $shift->break_duration_minutes }} minutes</td>
                        <td>
                            <span class="badge bg-{{ $shift->status === 'active' ? 'success' : 'secondary' }}">
                                {{ ucfirst($shift->status) }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Shift Modal -->
<div class="modal fade" id="newShiftModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('staff.shifts.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Add Working Shift</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Shift Name <span class="text-danger">*</span></label>
                    <input type="text" name="shift_name" class="form-control" required placeholder="e.g. Night Shift">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Start Time <span class="text-danger">*</span></label>
                        <input type="time" name="start_time" class="form-control" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold">End Time <span class="text-danger">*</span></label>
                        <input type="time" name="end_time" class="form-control" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Break Duration (Minutes) <span class="text-danger">*</span></label>
                    <input type="number" name="break_duration_minutes" class="form-control" value="60" required>
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
                <button type="submit" class="btn btn-primary">Save Shift</button>
            </div>
        </form>
    </div>
</div>
@endsection

