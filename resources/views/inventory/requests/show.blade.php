@extends('layouts.app')

@section('title', 'Stock Request ' . $stockRequest->request_no)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h4 class="fw-bold mb-0">Stock Request: {{ $stockRequest->request_no }}</h4>
            @switch($stockRequest->status)
                @case('requested')
                    <span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>Requested (Pending Review)</span>
                    @break
                @case('approved')
                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Approved</span>
                    @break
                @case('partially_approved')
                    <span class="badge bg-info text-dark"><i class="bi bi-pie-chart me-1"></i>Partially Approved</span>
                    @break
                @case('rejected')
                    <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Rejected</span>
                    @break
                @case('completed')
                    <span class="badge bg-primary"><i class="bi bi-check-all me-1"></i>Completed</span>
                    @break
                @default
                    <span class="badge bg-secondary">{{ ucfirst($stockRequest->status) }}</span>
            @endswitch
        </div>
        <p class="text-muted small mb-0">Requested on {{ \Carbon\Carbon::parse($stockRequest->request_date)->format('d M Y') }} by {{ $stockRequest->creator->name ?? 'User' }}</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('inventory.requests.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Requests
        </a>
        @if(in_array($stockRequest->status, ['approved', 'partially_approved']))
            <a href="{{ route('inventory.requests.dispatch.create', $stockRequest->id) }}" class="btn btn-primary">
                <i class="bi bi-truck me-1"></i> Prepare Dispatch
            </a>
        @endif
        @if(in_array($stockRequest->status, ['requested', 'pending', 'partially_approved']))
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approvalModal">
                <i class="bi bi-check-lg me-1"></i> Review & Approve
            </button>
            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                <i class="bi bi-x-lg me-1"></i> Reject
            </button>
        @endif
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Info Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <span class="text-muted small text-uppercase">Requesting Department</span>
                <div class="fw-bold fs-6 mt-1">{{ $stockRequest->fromDepartment->name ?? 'N/A' }}</div>
                <div class="text-muted small">
                    Location: {{ $stockRequest->fromGodown->name ?? 'Direct Delivery' }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <span class="text-muted small text-uppercase">Fulfilling Godown</span>
                <div class="fw-bold fs-6 mt-1 text-primary">{{ $stockRequest->sourceGodown->name ?? 'N/A' }}</div>
                <div class="text-muted small">
                    Type: {{ strtoupper($stockRequest->sourceGodown->location_type ?? 'warehouse') }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <span class="text-muted small text-uppercase">Priority & Timeline</span>
                <div class="fw-bold fs-6 mt-1 text-uppercase">{{ $stockRequest->priority }}</div>
                <div class="text-muted small">
                    Required by: {{ $stockRequest->required_date ? \Carbon\Carbon::parse($stockRequest->required_date)->format('d M Y') : 'Immediate' }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <span class="text-muted small text-uppercase">Review Audit</span>
                <div class="fw-bold fs-6 mt-1">{{ $stockRequest->approver->name ?? 'Pending' }}</div>
                <div class="text-muted small">
                    {{ $stockRequest->approved_at ? $stockRequest->approved_at->format('d M Y H:i') : 'Not reviewed yet' }}
                </div>
            </div>
        </div>
    </div>
</div>

@if($stockRequest->rejection_reason)
    <div class="alert alert-danger mb-4">
        <strong>Rejection Reason:</strong> {{ $stockRequest->rejection_reason }}
    </div>
@endif

<!-- Requested Items Matrix -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 border-0">
        <h5 class="fw-bold mb-0 text-primary"><i class="bi bi-box-seam me-2"></i>Requested Materials Breakdown</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Material / Item</th>
                        <th>UOM</th>
                        <th class="text-end">Requested Qty</th>
                        <th class="text-end">Approved Qty</th>
                        <th class="text-end">Dispatched Qty</th>
                        <th class="text-end">Received Qty</th>
                        <th>Fulfillment Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stockRequest->items as $idx => $item)
                        @php
                            $approved = (float)$item->approved_qty;
                            $dispatched = (float)$item->dispatched_qty;
                            $received = (float)$item->received_qty;
                            $requested = (float)$item->requested_qty;
                        @endphp
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td>
                                <span class="fw-bold">{{ $item->item->name ?? 'Item' }}</span>
                                <div class="text-muted small"><code>{{ $item->item->code ?? '' }}</code></div>
                            </td>
                            <td>{{ $item->item->unit->symbol ?? $item->item->unit->name ?? 'Units' }}</td>
                            <td class="text-end fw-bold">{{ number_format($requested, 3) }}</td>
                            <td class="text-end fw-bold text-success">{{ number_format($approved, 3) }}</td>
                            <td class="text-end text-primary">{{ number_format($dispatched, 3) }}</td>
                            <td class="text-end text-success">{{ number_format($received, 3) }}</td>
                            <td>
                                @if($stockRequest->status === 'requested')
                                    <span class="badge bg-light text-dark border">Pending Approval</span>
                                @elseif($dispatched >= $approved && $approved > 0)
                                    <span class="badge bg-success">Dispatched</span>
                                @elseif($dispatched > 0)
                                    <span class="badge bg-info text-dark">Partially Dispatched</span>
                                @elseif($approved > 0)
                                    <span class="badge bg-warning text-dark">Ready for Dispatch</span>
                                @else
                                    <span class="badge bg-danger">Not Approved</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Dispatches Linked to this Request -->
@if($stockRequest->transfers->isNotEmpty())
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 border-0">
            <h5 class="fw-bold mb-0 text-primary"><i class="bi bi-truck me-2"></i>Dispatches & Shipments</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Transfer #</th>
                            <th>Date</th>
                            <th>Dispatched By</th>
                            <th>Received By</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stockRequest->transfers as $trf)
                            <tr>
                                <td><span class="badge bg-light text-primary border fw-bold">{{ $trf->transfer_no }}</span></td>
                                <td>{{ \Carbon\Carbon::parse($trf->transfer_date)->format('d M Y') }}</td>
                                <td>{{ $trf->dispatcher->name ?? 'Dispatcher' }}</td>
                                <td>{{ $trf->receiver->name ?? '-' }}</td>
                                <td>
                                    @switch($trf->status)
                                        @case('in_transit')
                                            <span class="badge bg-info text-dark">In-Transit</span>
                                            @break
                                        @case('partially_received')
                                            <span class="badge bg-warning text-dark">Partially Received</span>
                                            @break
                                        @case('completed')
                                            <span class="badge bg-success">Completed</span>
                                            @break
                                        @default
                                            <span class="badge bg-secondary">{{ ucfirst($trf->status) }}</span>
                                    @endswitch
                                </td>
                                <td class="text-end">
                                    @if(in_array($trf->status, ['in_transit', 'partially_received']))
                                        <a href="{{ route('inventory.transfers.receipt.create', $trf->id) }}" class="btn btn-sm btn-success">
                                            <i class="bi bi-box-seam me-1"></i> Confirm Receipt
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif

<!-- Approval Modal -->
<div class="modal fade" id="approvalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('inventory.requests.approve', $stockRequest->id) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Review & Approve Stock Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">You may approve full requested quantities or adjust quantities to grant partial approval.</p>
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Item</th>
                                <th class="text-end" style="width: 25%;">Requested Qty</th>
                                <th class="text-end" style="width: 35%;">Approved Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stockRequest->items as $item)
                                <tr>
                                    <td>
                                        <strong>{{ $item->item->name }}</strong>
                                        <div class="small text-muted">{{ $item->item->unit->symbol ?? '' }}</div>
                                    </td>
                                    <td class="text-end fw-bold">{{ number_format($item->requested_qty, 3) }}</td>
                                    <td>
                                        <input type="number" step="0.001" min="0" max="{{ $item->requested_qty }}"
                                            name="items[{{ $item->id }}][approved_qty]"
                                            class="form-control form-control-sm text-end"
                                            value="{{ $item->approved_qty > 0 ? $item->approved_qty : $item->requested_qty }}" required>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="mt-3">
                        <label class="form-label small">Review Remarks</label>
                        <textarea name="remarks" class="form-control form-control-sm" rows="2" placeholder="Approval notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i> Save Approval</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('inventory.requests.reject', $stockRequest->id) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-danger">Reject Stock Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Please provide a mandatory reason explaining why this request cannot be fulfilled.</p>
                    <div class="mb-3">
                        <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" class="form-control" rows="3" required placeholder="e.g. Stock reserved for export orders or item obsolete"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-x-circle me-1"></i> Confirm Rejection</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
