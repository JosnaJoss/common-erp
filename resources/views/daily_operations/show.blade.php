@extends('layouts.app')

@section('title', 'Operation ' . $daily_operation->operation_number)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h4 class="mb-0 fw-bold">{{ $daily_operation->operation_number }}</h4>
            @switch($daily_operation->approval_status)
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
                    <span class="badge bg-light text-dark">{{ $daily_operation->approval_status }}</span>
            @endswitch

            @if($daily_operation->stock_posted)
            <span class="badge bg-success-subtle text-success border">
                <i class="bi bi-check-circle me-1"></i> Stock Ledger Posted
            </span>
            @else
            <span class="badge bg-warning-subtle text-dark border">
                Stock Not Posted
            </span>
            @endif
        </div>
        <small class="text-muted">
            Department: <strong>{{ $daily_operation->department?->name }}</strong> | 
            Process: <strong>{{ $daily_operation->process_name }}</strong> | 
            Date: <strong>{{ $daily_operation->operation_date->format('d M Y') }}</strong>
        </small>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('daily_operations.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>

        @if($daily_operation->approval_status === 'draft')
        <form method="POST" action="{{ route('daily_operations.submit', $daily_operation->id) }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-info text-white">
                <i class="bi bi-send me-1"></i> Submit for Approval
            </button>
        </form>
        @endif

        @if(in_array($daily_operation->approval_status, ['submitted', 'supervisor_approved']) && !$daily_operation->stock_posted)
        <form method="POST" action="{{ route('daily_operations.approve', $daily_operation->id) }}" class="d-inline">
            @csrf
            <input type="hidden" name="target_status" value="department_head_approved">
            <button type="submit" class="btn btn-success" onclick="return confirm('Approve this daily operation and post inventory stock movements?')">
                <i class="bi bi-check-lg me-1"></i> Approve & Post Stock
            </button>
        </form>

        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
            <i class="bi bi-x-circle me-1"></i> Reject
        </button>
        @endif
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

<!-- Overview Cards Row -->
<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card card-custom p-3 border-start border-4 border-primary">
            <div class="text-muted small">Total Labor Time & Cost</div>
            <div class="h5 fw-bold mb-0 text-dark">
                {{ number_format($daily_operation->employees->sum('hours_worked'), 1) }} hrs
                <small class="text-muted fs-6 fw-normal">(₹{{ number_format((float)$daily_operation->total_labor_cost, 2) }})</small>
            </div>
            <small class="text-muted">{{ $daily_operation->employees->count() }} employees allocated</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom p-3 border-start border-4 border-danger">
            <div class="text-muted small">Material Consumed</div>
            <div class="h5 fw-bold mb-0 text-danger">
                {{ number_format($daily_operation->materials->sum('consumed_quantity'), 2) }} units
            </div>
            <small class="text-muted">Est. Cost: ₹{{ number_format((float)$daily_operation->total_material_cost, 2) }}</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom p-3 border-start border-4 border-success">
            <div class="text-muted small">Finished Output Produced</div>
            <div class="h5 fw-bold mb-0 text-success">
                {{ number_format($daily_operation->outputs->sum('produced_quantity'), 2) }} units
            </div>
            <small class="text-muted">{{ $daily_operation->outputs->count() }} output batches</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom p-3 border-start border-4 border-warning">
            <div class="text-muted small">Total Wastage & Losses</div>
            <div class="h5 fw-bold mb-0 text-warning">
                {{ number_format($daily_operation->wastages->sum('quantity'), 2) }} units
            </div>
            <small class="text-muted">Est. Loss: ₹{{ number_format((float)$daily_operation->total_wastage_cost, 2) }}</small>
        </div>
    </div>
</div>

<!-- Header Information Details Card -->
<div class="card card-custom mb-3">
    <div class="card-header bg-white py-2 fw-semibold">
        <i class="bi bi-info-circle text-primary me-2"></i>Operational Process Information
    </div>
    <div class="card-body">
        <div class="row g-3 small">
            <div class="col-md-3">
                <span class="text-muted d-block">Department Head</span>
                <span class="fw-semibold">{{ $daily_operation->departmentHead?->full_name ?? 'Not specified' }}</span>
            </div>
            <div class="col-md-3">
                <span class="text-muted d-block">Supervisor</span>
                <span class="fw-semibold">{{ $daily_operation->supervisor?->full_name ?? 'Not specified' }}</span>
            </div>
            <div class="col-md-3">
                <span class="text-muted d-block">Timing</span>
                <span class="fw-semibold">
                    {{ $daily_operation->start_time ? substr($daily_operation->start_time, 0, 5) : '-' }} to 
                    {{ $daily_operation->end_time ? substr($daily_operation->end_time, 0, 5) : '-' }}
                </span>
            </div>
            <div class="col-md-3">
                <span class="text-muted d-block">Work Order / Job Ref</span>
                <span class="fw-semibold">{{ $daily_operation->work_order_ref ?? 'None' }}</span>
            </div>
            @if($daily_operation->work_description)
            <div class="col-12">
                <span class="text-muted d-block">Work Description & Activity Notes</span>
                <div class="p-2 bg-light rounded border">{{ $daily_operation->work_description }}</div>
            </div>
            @endif
            @if($daily_operation->approval_remarks)
            <div class="col-12">
                <span class="text-muted d-block">Approval Remarks</span>
                <div class="p-2 bg-success-subtle text-success rounded border border-success">{{ $daily_operation->approval_remarks }}</div>
            </div>
            @endif
            @if($daily_operation->rejection_reason)
            <div class="col-12">
                <span class="text-muted d-block">Rejection Reason</span>
                <div class="p-2 bg-danger-subtle text-danger rounded border border-danger">{{ $daily_operation->rejection_reason }}</div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- 1. Participating Employees Table -->
<div class="card card-custom mb-3">
    <div class="card-header bg-white py-2 fw-semibold">
        <i class="bi bi-people text-primary me-2"></i>1. Participating Workers & Labor Allocation
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Employee Name</th>
                        <th>Employee ID</th>
                        <th>Employment Arrangement</th>
                        <th class="text-center">Hours Worked</th>
                        <th class="text-center">Units Completed</th>
                        <th class="text-end">Wage / Unit Rate</th>
                        <th class="text-end pe-3">Earned Wage (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($daily_operation->employees as $emp)
                    <tr>
                        <td class="ps-3 fw-bold">{{ $emp->staff?->full_name ?? 'N/A' }}</td>
                        <td>{{ $emp->staff?->employee_id ?? 'N/A' }}</td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                {{ strtoupper(str_replace('_', ' ', $emp->employment_type)) }}
                            </span>
                        </td>
                        <td class="text-center fw-semibold">{{ number_format($emp->hours_worked, 2) }} hrs</td>
                        <td class="text-center">{{ number_format($emp->units_completed, 0) }}</td>
                        <td class="text-end">₹{{ number_format((float)$emp->rate_per_unit, 2) }}</td>
                        <td class="text-end pe-3 fw-bold text-dark">₹{{ number_format((float)$emp->wage_earned, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-3 text-muted">No employee participation records logged.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 2. Consumed Materials Table -->
<div class="card card-custom mb-3">
    <div class="card-header bg-white py-2 fw-semibold">
        <i class="bi bi-box-arrow-right text-danger me-2"></i>2. Raw Materials & Stock Consumed
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Material / Product</th>
                        <th>Source Warehouse</th>
                        <th>Batch #</th>
                        <th class="text-center">Opening Qty</th>
                        <th class="text-center">Issued Qty</th>
                        <th class="text-center">Consumed Qty</th>
                        <th class="text-center">Returned Qty</th>
                        <th class="text-center">Remaining Qty</th>
                        <th class="text-end pe-3">Total Cost (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($daily_operation->materials as $mat)
                    <tr>
                        <td class="ps-3 fw-bold">{{ $mat->item?->name ?? 'N/A' }}</td>
                        <td>{{ $mat->sourceGodown?->name ?? 'N/A' }}</td>
                        <td><code>{{ $mat->batch_no }}</code></td>
                        <td class="text-center">{{ number_format($mat->opening_quantity, 2) }}</td>
                        <td class="text-center">{{ number_format($mat->issued_quantity, 2) }}</td>
                        <td class="text-center fw-bold text-danger">{{ number_format($mat->consumed_quantity, 3) }} {{ $mat->item?->unit?->symbol }}</td>
                        <td class="text-center">{{ number_format($mat->returned_quantity, 2) }}</td>
                        <td class="text-center">{{ number_format($mat->remaining_quantity, 2) }}</td>
                        <td class="text-end pe-3 fw-semibold">₹{{ number_format((float)$mat->total_cost, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-3 text-muted">No raw material consumption recorded.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 3. Produced Outputs Table -->
<div class="card card-custom mb-3">
    <div class="card-header bg-white py-2 fw-semibold">
        <i class="bi bi-box-arrow-in-left text-success me-2"></i>3. Production Output & Finished Goods
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Produced Product</th>
                        <th>Destination Warehouse</th>
                        <th>Production Batch #</th>
                        <th class="text-center">Produced Qty</th>
                        <th class="text-center">Rejected Qty</th>
                        <th class="text-center">Quality Status</th>
                        <th class="text-end pe-3">Unit Valuation (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($daily_operation->outputs as $out)
                    <tr>
                        <td class="ps-3 fw-bold">{{ $out->item?->name ?? 'N/A' }}</td>
                        <td>{{ $out->destGodown?->name ?? 'N/A' }}</td>
                        <td><code>{{ $out->batch_number }}</code></td>
                        <td class="text-center fw-bold text-success">{{ number_format($out->produced_quantity, 3) }} {{ $out->item?->unit?->symbol }}</td>
                        <td class="text-center">{{ number_format($out->rejected_quantity, 2) }}</td>
                        <td class="text-center">
                            @if($out->quality_status === 'passed')
                            <span class="badge bg-success-subtle text-success border">Passed</span>
                            @else
                            <span class="badge bg-warning-subtle text-dark border">{{ $out->quality_status }}</span>
                            @endif
                        </td>
                        <td class="text-end pe-3">₹{{ number_format((float)$out->unit_cost, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-3 text-muted">No finished product output recorded.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 4. Granular Wastage & Recovery Table -->
<div class="card card-custom mb-3">
    <div class="card-header bg-white py-2 fw-semibold">
        <i class="bi bi-trash3 text-warning me-2"></i>4. Activity Wastage, Scrap & Recovery Breakdown
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Wastage Item / Description</th>
                        <th>Loss Category</th>
                        <th class="text-center">Wastage Quantity</th>
                        <th class="text-center">Recoverable / Scrap Qty</th>
                        <th class="text-end">Estimated Loss Value</th>
                        <th class="pe-3">Reason / Cause</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($daily_operation->wastages as $w)
                    <tr>
                        <td class="ps-3 fw-semibold">{{ $w->wastage_name }}</td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                {{ strtoupper(str_replace('_', ' ', $w->wastage_type)) }}
                            </span>
                        </td>
                        <td class="text-center fw-bold text-warning">{{ number_format($w->quantity, 3) }} {{ $w->unit }}</td>
                        <td class="text-center">{{ number_format($w->recoverable_quantity, 3) }} {{ $w->unit }}</td>
                        <td class="text-end fw-semibold text-danger">₹{{ number_format((float)$w->estimated_cost, 2) }}</td>
                        <td class="pe-3 text-muted">{{ $w->reason ?? 'Process standard' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-3 text-muted">No activity wastage or material loss recorded.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 5. Output Distribution & Transfers Table -->
@if($daily_operation->distributions->isNotEmpty())
<div class="card card-custom mb-3">
    <div class="card-header bg-white py-2 fw-semibold">
        <i class="bi bi-truck text-info me-2"></i>5. Output Distribution to Sales Counters, Vans & Warehouses
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Distributed Product</th>
                        <th>Destination Location</th>
                        <th>Destination Type</th>
                        <th>Batch #</th>
                        <th class="text-center">Transferred Quantity</th>
                        <th class="text-center">Transfer Ref #</th>
                        <th class="text-end pe-3">Transfer Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($daily_operation->distributions as $dst)
                    <tr>
                        <td class="ps-3 fw-bold">{{ $dst->item?->name ?? 'N/A' }}</td>
                        <td>{{ $dst->destinationGodown?->name ?? 'N/A' }}</td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                {{ strtoupper(str_replace('_', ' ', $dst->destination_type)) }}
                            </span>
                        </td>
                        <td><code>{{ $dst->batch_no }}</code></td>
                        <td class="text-center fw-bold text-info">{{ number_format($dst->quantity, 3) }} {{ $dst->item?->unit?->symbol }}</td>
                        <td class="text-center">
                            @if($dst->stockTransfer)
                            <code>{{ $dst->stockTransfer->transfer_no }}</code>
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-end pe-3">
                            <span class="badge bg-success-subtle text-success border">
                                <i class="bi bi-check me-1"></i>{{ ucfirst($dst->status) }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<!-- 6. Auditable Stock Ledger Movements Generated -->
@if($daily_operation->stockMovements->isNotEmpty())
<div class="card card-custom mb-4">
    <div class="card-header bg-white py-2 fw-semibold">
        <i class="bi bi-journal-check text-secondary me-2"></i>6. Auditable Inventory Stock Movements Posted
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Movement Date</th>
                        <th>Movement Type</th>
                        <th>Warehouse / Godown</th>
                        <th>Item</th>
                        <th class="text-center">Quantity</th>
                        <th class="text-center">Stock Balance After</th>
                        <th class="pe-3">Movement Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($daily_operation->stockMovements as $mv)
                    <tr>
                        <td class="ps-3">{{ $mv->movement_date }}</td>
                        <td>
                            @if($mv->quantity > 0)
                            <span class="badge bg-success-subtle text-success border"><i class="bi bi-arrow-down-left"></i> INWARD</span>
                            @else
                            <span class="badge bg-danger-subtle text-danger border"><i class="bi bi-arrow-up-right"></i> OUTWARD</span>
                            @endif
                        </td>
                        <td>{{ $mv->godown?->name }}</td>
                        <td class="fw-semibold">{{ $mv->item?->name }}</td>
                        <td class="text-center fw-bold {{ $mv->quantity > 0 ? 'text-success' : 'text-danger' }}">
                            {{ number_format($mv->quantity, 3) }} {{ $mv->item?->unit?->symbol }}
                        </td>
                        <td class="text-center fw-semibold">{{ number_format($mv->balance_after, 3) }}</td>
                        <td class="pe-3 text-muted">{{ $mv->remarks }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('daily_operations.reject', $daily_operation->id) }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reject Daily Operation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label small fw-semibold">Reason for Rejection <span class="text-danger">*</span></label>
                    <textarea name="rejection_reason" class="form-control" rows="3" placeholder="Provide reason or corrections required..." required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm Rejection</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

