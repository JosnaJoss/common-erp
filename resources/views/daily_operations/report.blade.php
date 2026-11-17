@extends('layouts.app')

@section('title', 'Daily Operations Intelligence Report')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 d-print-none">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-file-earmark-bar-graph text-primary me-2"></i>Daily Operations Intelligence Report</h4>
        <small class="text-muted">Consolidated date-wise, department-wise operational performance, labor hours, material consumption and wastage</small>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Print Report
        </button>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-outline-success">
            <i class="bi bi-file-earmark-excel me-1"></i> Export to CSV
        </a>
        <a href="{{ route('daily_operations.index') }}" class="btn btn-outline-primary">
            <i class="bi bi-list-task me-1"></i> Operations List
        </a>
    </div>
</div>

<!-- Filter Bar -->
<div class="card card-custom mb-3 d-print-none">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('reports.daily_operations.index') }}" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">From Date</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">To Date</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Department</label>
                <select name="department_id" class="form-select form-select-sm">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                        {{ $dept->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Supervisor</label>
                <select name="supervisor_id" class="form-select form-select-sm">
                    <option value="">All Supervisors</option>
                    @foreach($supervisors as $sup)
                    <option value="{{ $sup->id }}" {{ request('supervisor_id') == $sup->id ? 'selected' : '' }}>
                        {{ $sup->full_name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-filter me-1"></i> Generate Report
                </button>
                <a href="{{ route('reports.daily_operations.index') }}" class="btn btn-sm btn-light border">
                    Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Print Header (Visible only when printed) -->
<div class="d-none d-print-block mb-4">
    <div class="text-center">
        <h3 class="fw-bold mb-1">{{ \App\Services\CompanyContextService::getActiveCompany()?->name ?? 'Common ERP' }}</h3>
        <h5 class="mb-1">Daily Operations & Work Summary Report</h5>
        <p class="text-muted small">Period: {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} to {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}</p>
    </div>
</div>

<!-- 6 KPI Metric Cards Row -->
<div class="row g-3 mb-4">
    <div class="col-md-2 col-6">
        <div class="card card-custom p-3 text-center border-top border-3 border-primary">
            <span class="text-muted small">Total Activities</span>
            <h4 class="fw-bold mb-0 text-primary">{{ $totalOperations }}</h4>
            <small class="text-muted">Recorded</small>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card card-custom p-3 text-center border-top border-3 border-info">
            <span class="text-muted small">Labor Hours</span>
            <h4 class="fw-bold mb-0 text-dark">{{ number_format($totalLaborHours, 1) }}h</h4>
            <small class="text-muted">₹{{ number_format($totalLaborCost, 0) }}</small>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card card-custom p-3 text-center border-top border-3 border-danger">
            <span class="text-muted small">Raw Mat. Consumed</span>
            <h4 class="fw-bold mb-0 text-danger">{{ number_format($totalConsumedQty, 1) }}</h4>
            <small class="text-muted">₹{{ number_format($totalMaterialCost, 0) }}</small>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card card-custom p-3 text-center border-top border-3 border-success">
            <span class="text-muted small">Output Produced</span>
            <h4 class="fw-bold mb-0 text-success">{{ number_format($totalProducedQty, 1) }}</h4>
            <small class="text-muted">Units generated</small>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card card-custom p-3 text-center border-top border-3 border-warning">
            <span class="text-muted small">Wastage & Loss</span>
            <h4 class="fw-bold mb-0 text-warning">₹{{ number_format($totalWastageCost, 0) }}</h4>
            <small class="text-muted">Est. loss value</small>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card card-custom p-3 text-center border-top border-3 border-secondary">
            <span class="text-muted small">Net Recovery %</span>
            @php
                $efficiency = ($totalConsumedQty > 0) ? ($totalProducedQty / $totalConsumedQty) * 100 : 100;
            @endphp
            <h4 class="fw-bold mb-0 text-dark">{{ number_format($efficiency, 1) }}%</h4>
            <small class="text-muted">Output / Input ratio</small>
        </div>
    </div>
</div>

<!-- Department-wise Operational Breakdown -->
<div class="card card-custom mb-4">
    <div class="card-header bg-white py-2 fw-semibold">
        <i class="bi bi-diagram-3 text-primary me-2"></i>Department-wise Operational Performance Summary
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Department Name</th>
                        <th class="text-center">Activities Completed</th>
                        <th class="text-center">Total Labor Hours</th>
                        <th class="text-center">Finished Output</th>
                        <th class="text-end pe-3">Wastage Cost Impact</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($departmentSummaries as $deptSummary)
                    <tr>
                        <td class="ps-3 fw-bold">{{ $deptSummary['department_name'] }}</td>
                        <td class="text-center"><span class="badge bg-light text-dark border">{{ $deptSummary['operations_count'] }}</span></td>
                        <td class="text-center fw-semibold">{{ number_format($deptSummary['labor_hours'], 1) }} hrs</td>
                        <td class="text-center fw-bold text-success">{{ number_format($deptSummary['produced_quantity'], 2) }} units</td>
                        <td class="text-end pe-3 fw-semibold text-danger">₹{{ number_format($deptSummary['wastage_cost'], 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-3 text-muted">No department operations found for selected period.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Complete Activity Operational Records -->
<div class="card card-custom mb-4">
    <div class="card-header bg-white py-2 fw-semibold">
        <i class="bi bi-card-checklist text-primary me-2"></i>Activity-by-Activity Operational Log
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Operation #</th>
                        <th>Date</th>
                        <th>Department</th>
                        <th>Process / Activity</th>
                        <th>Labor Allocated</th>
                        <th>Materials Consumed</th>
                        <th>Produced Output</th>
                        <th>Wastage Logged</th>
                        <th>Approval</th>
                        <th class="text-end pe-3 d-print-none">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($operations as $op)
                    <tr>
                        <td class="ps-3 fw-bold">
                            <a href="{{ route('daily_operations.show', $op->id) }}" class="text-primary text-decoration-none">
                                {{ $op->operation_number }}
                            </a>
                        </td>
                        <td>{{ $op->operation_date->format('d M Y') }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $op->department?->name }}</span></td>
                        <td>
                            <span class="fw-semibold">{{ $op->process_name }}</span>
                            @if($op->supervisor)
                            <div class="text-muted" style="font-size: 0.72rem;">Sup: {{ $op->supervisor->full_name }}</div>
                            @endif
                        </td>
                        <td>
                            <div>{{ $op->employees->count() }} staff ({{ number_format($op->employees->sum('hours_worked'), 1) }}h)</div>
                            <small class="text-muted">₹{{ number_format((float)$op->total_labor_cost, 0) }}</small>
                        </td>
                        <td>
                            @forelse($op->materials as $m)
                            <div>{{ $m->item?->name }}: <span class="fw-semibold text-danger">{{ number_format($m->consumed_quantity, 2) }} {{ $m->item?->unit?->symbol }}</span></div>
                            @empty
                            <span class="text-muted">-</span>
                            @endforelse
                        </td>
                        <td>
                            @forelse($op->outputs as $o)
                            <div>{{ $o->item?->name }}: <span class="fw-semibold text-success">{{ number_format($o->produced_quantity, 2) }} {{ $o->item?->unit?->symbol }}</span></div>
                            @empty
                            <span class="text-muted">-</span>
                            @endforelse
                        </td>
                        <td>
                            @if($op->wastages->isNotEmpty())
                            <span class="text-warning fw-semibold">{{ number_format($op->wastages->sum('quantity'), 2) }}</span>
                            @if($op->total_wastage_cost > 0)
                            <small class="text-muted d-block">₹{{ number_format((float)$op->total_wastage_cost, 0) }}</small>
                            @endif
                            @else
                            <span class="text-muted">0</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                {{ strtoupper(str_replace('_', ' ', $op->approval_status)) }}
                            </span>
                        </td>
                        <td class="text-end pe-3 d-print-none">
                            <a href="{{ route('daily_operations.show', $op->id) }}" class="btn btn-sm btn-outline-primary">
                                View
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-4 text-muted">
                            No operational activities logged for the selected date range.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

