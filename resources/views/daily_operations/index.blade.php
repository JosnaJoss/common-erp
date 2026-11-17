@extends('layouts.app')

@section('title', 'Daily Operations & Work Reports')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-clock-history text-primary me-2"></i>Daily Operations & Work Reports</h4>
        <small class="text-muted">Activity-wise daily operational logs, employee participation, material consumption, outputs and wastage</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('reports.daily_operations.index') }}" class="btn btn-outline-primary">
            <i class="bi bi-bar-chart-line me-1"></i> Operations Intelligence Report
        </a>
        <a href="{{ route('daily_operations.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> New Daily Operation
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('warning'))
<div class="alert alert-warning alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('warning') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<!-- Filters -->
<div class="card card-custom mb-3">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('daily_operations.index') }}" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Date</label>
                <input type="date" name="date" class="form-control form-control-sm" value="{{ request('date') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Department</label>
                <select name="department_id" class="form-select form-select-sm">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                        {{ $dept->name }} ({{ strtoupper($dept->department_type ?? 'general') }})
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Approval Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="submitted" {{ request('status') == 'submitted' ? 'selected' : '' }}>Submitted (Pending Approval)</option>
                    <option value="supervisor_approved" {{ request('status') == 'supervisor_approved' ? 'selected' : '' }}>Supervisor Approved</option>
                    <option value="department_head_approved" {{ request('status') == 'department_head_approved' ? 'selected' : '' }}>Department Head Approved</option>
                    <option value="admin_approved" {{ request('status') == 'admin_approved' ? 'selected' : '' }}>Admin Approved</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-filter me-1"></i> Apply Filters
                </button>
                <a href="{{ route('daily_operations.index') }}" class="btn btn-sm btn-light border">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Operations Listing Table -->
<div class="card card-custom">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th class="ps-3">Operation #</th>
                        <th>Date</th>
                        <th>Department</th>
                        <th>Process / Activity</th>
                        <th>Supervisor / Head</th>
                        <th class="text-center">Labor</th>
                        <th class="text-center">Outputs</th>
                        <th class="text-center">Wastage</th>
                        <th>Status</th>
                        <th>Stock Posted</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="small">
                    @forelse($operations as $op)
                    <tr>
                        <td class="ps-3 fw-bold">
                            <a href="{{ route('daily_operations.show', $op->id) }}" class="text-primary text-decoration-none">
                                {{ $op->operation_number }}
                            </a>
                            @if($op->work_order_ref)
                            <div class="text-muted" style="font-size: 0.72rem;">Ref: {{ $op->work_order_ref }}</div>
                            @endif
                        </td>
                        <td>
                            <div>{{ $op->operation_date->format('d M Y') }}</div>
                            @if($op->start_time)
                            <span class="text-muted" style="font-size: 0.75rem;">
                                {{ substr($op->start_time, 0, 5) }} - {{ substr($op->end_time, 0, 5) }}
                            </span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                {{ $op->department?->name ?? 'N/A' }}
                            </span>
                        </td>
                        <td>
                            <span class="fw-semibold">{{ $op->process_name }}</span>
                            @if($op->work_description)
                            <div class="text-muted text-truncate" style="max-width: 200px;" title="{{ $op->work_description }}">
                                {{ $op->work_description }}
                            </div>
                            @endif
                        </td>
                        <td>
                            @if($op->supervisor)
                            <div class="text-dark"><i class="bi bi-person me-1"></i>{{ $op->supervisor->full_name }}</div>
                            @elseif($op->departmentHead)
                            <div class="text-dark"><i class="bi bi-person-badge me-1"></i>{{ $op->departmentHead->full_name }}</div>
                            @else
                            <span class="text-muted">Unassigned</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge bg-secondary-subtle text-secondary border">
                                {{ $op->employees->count() }} workers / {{ number_format($op->employees->sum('hours_worked'), 1) }}h
                            </span>
                        </td>
                        <td class="text-center">
                            @if($op->outputs->isNotEmpty())
                            <span class="badge bg-success-subtle text-success border">
                                {{ number_format($op->outputs->sum('produced_quantity'), 2) }} units
                            </span>
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($op->wastages->isNotEmpty())
                            <span class="badge bg-warning-subtle text-dark border" title="{{ $op->wastages->pluck('wastage_name')->implode(', ') }}">
                                {{ number_format($op->wastages->sum('quantity'), 2) }}
                                @if($op->total_wastage_cost > 0)
                                (₹{{ number_format($op->total_wastage_cost, 0) }})
                                @endif
                            </span>
                            @else
                            <span class="text-muted">0</span>
                            @endif
                        </td>
                        <td>
                            @switch($op->approval_status)
                                @case('draft')
                                    <span class="badge bg-secondary">Draft</span>
                                    @break
                                @case('submitted')
                                    <span class="badge bg-info text-dark">Submitted</span>
                                    @break
                                @case('supervisor_approved')
                                    <span class="badge bg-primary">Supervisor Approved</span>
                                    @break
                                @case('department_head_approved')
                                    <span class="badge bg-success">Dept Head Approved</span>
                                    @break
                                @case('admin_approved')
                                    <span class="badge bg-success">Admin Approved</span>
                                    @break
                                @case('rejected')
                                    <span class="badge bg-danger">Rejected</span>
                                    @break
                                @default
                                    <span class="badge bg-light text-dark">{{ $op->approval_status }}</span>
                            @endswitch
                        </td>
                        <td>
                            @if($op->stock_posted)
                            <span class="badge bg-success-subtle text-success border">
                                <i class="bi bi-check-circle me-1"></i> Posted
                            </span>
                            @else
                            <span class="badge bg-secondary-subtle text-muted border">
                                Pending
                            </span>
                            @endif
                        </td>
                        <td class="text-end pe-3">
                            <a href="{{ route('daily_operations.show', $op->id) }}" class="btn btn-sm btn-outline-primary" title="View Details">
                                <i class="bi bi-eye"></i> View
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            No daily operational records found matching the criteria.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($operations->hasPages())
    <div class="card-footer bg-white border-top p-2">
        {{ $operations->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection

