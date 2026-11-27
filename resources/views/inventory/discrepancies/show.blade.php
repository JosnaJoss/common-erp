@extends('layouts.app')

@section('title', 'Investigate Discrepancy #' . $discrepancy->id)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h4 class="fw-bold mb-0">Discrepancy #{{ $discrepancy->id }}: {{ ucwords(str_replace('_', ' ', $discrepancy->discrepancy_type)) }}</h4>
            @switch($discrepancy->status)
                @case('reported')
                    <span class="badge bg-warning text-dark">Reported</span>
                    @break
                @case('under_investigation')
                    <span class="badge bg-info text-dark">Under Investigation</span>
                    @break
                @case('pending_approval')
                    <span class="badge bg-primary">Pending Write-off Approval</span>
                    @break
                @case('resolved')
                    <span class="badge bg-success">Resolved ({{ ucwords(str_replace('_', ' ', $discrepancy->resolution_type ?? '')) }})</span>
                    @break
                @default
                    <span class="badge bg-secondary">{{ ucfirst($discrepancy->status) }}</span>
            @endswitch
        </div>
        <p class="text-muted small mb-0">Linked to Transfer: {{ $discrepancy->transfer->transfer_no ?? 'N/A' }} | Reported by {{ $discrepancy->creator->name ?? 'User' }}</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('inventory.discrepancies.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Discrepancies
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('info'))
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="bi bi-info-circle me-2"></i>{{ session('info') }}
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

<!-- Discrepancy Overview Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <span class="text-muted small text-uppercase">Material & Batch</span>
                <div class="fw-bold fs-6 mt-1">{{ $discrepancy->item->name }}</div>
                <div class="text-muted small">Batch: <code>{{ $discrepancy->batch->batch_no ?? 'DEFAULT' }}</code></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <span class="text-muted small text-uppercase">Variance Quantities</span>
                <div class="fw-bold fs-6 mt-1 text-danger">Short/Damaged: {{ number_format($discrepancy->discrepancy_qty, 3) }} {{ $discrepancy->item->unit->symbol ?? '' }}</div>
                <div class="text-muted small">Dispatched: {{ number_format($discrepancy->dispatched_qty, 3) }} | Received: {{ number_format($discrepancy->received_qty, 3) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <span class="text-muted small text-uppercase">Locations</span>
                <div class="small mt-1"><strong>From:</strong> {{ $discrepancy->sourceGodown->name ?? 'Warehouse' }}</div>
                <div class="small"><strong>To:</strong> {{ $discrepancy->destGodown->name ?? 'Location' }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <span class="text-muted small text-uppercase">Reason Reported</span>
                <div class="fw-bold fs-6 mt-1 text-truncate" title="{{ $discrepancy->reason }}">{{ $discrepancy->reason }}</div>
                <div class="text-muted small">{{ $discrepancy->remarks ?: 'No additional notes' }}</div>
            </div>
        </div>
    </div>
</div>

@if($discrepancy->investigation_remarks)
    <div class="card shadow-sm border-0 mb-4 bg-light">
        <div class="card-body">
            <h6 class="fw-bold text-primary mb-1"><i class="bi bi-search me-2"></i>Investigation Findings</h6>
            <p class="mb-0">{{ $discrepancy->investigation_remarks }}</p>
            <small class="text-muted mt-1 d-block">Recorded by: {{ $discrepancy->investigator->name ?? 'User' }} on {{ $discrepancy->investigated_at ? $discrepancy->investigated_at->format('d M Y H:i') : '' }}</small>
        </div>
    </div>
@endif

@if($discrepancy->stockAdjustment && $discrepancy->stockAdjustment->journalEntry)
    <div class="alert alert-success mb-4">
        <h6 class="fw-bold mb-1"><i class="bi bi-journal-check me-2"></i>Financial Write-Off Journal Posted</h6>
        <div>Adjustment Reference: <strong>{{ $discrepancy->stockAdjustment->adjustment_no }}</strong> | Journal Entry: <strong>#{{ $discrepancy->stockAdjustment->journal_entry_id }}</strong></div>
        <div class="small text-muted">Amount has been debited to inventory loss expense and credited to stock on hand asset.</div>
    </div>
@endif

@if($discrepancy->status !== 'resolved')
    <!-- Investigation & Resolution Actions -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 border-0">
            <h5 class="fw-bold mb-0 text-primary"><i class="bi bi-tools me-2"></i>Investigation & Resolution Actions</h5>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <!-- Option 1: Add Investigation Remarks -->
                <div class="col-md-6 border-end">
                    <h6 class="fw-bold"><i class="bi bi-chat-left-dots me-1"></i> Update Investigation Findings</h6>
                    <form method="POST" action="{{ route('inventory.discrepancies.investigate', $discrepancy->id) }}">
                        @csrf
                        <div class="mb-3">
                            <textarea name="investigation_remarks" class="form-control form-control-sm" rows="3" required placeholder="Detail findings, driver statements, transit condition...">{{ $discrepancy->investigation_remarks }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-sm btn-info text-white">
                            <i class="bi bi-save me-1"></i> Save Investigation Notes
                        </button>
                    </form>

                    <hr class="my-3">

                    <h6 class="fw-bold text-danger"><i class="bi bi-file-earmark-x me-1"></i> Financial Loss Write-Off Workflow</h6>
                    <p class="small text-muted mb-2">If goods are confirmed destroyed or stolen, submit a write-off. An authorized manager will review and approve with automatic double-entry journal posting.</p>
                    
                    @if($discrepancy->status === 'pending_approval')
                        <div class="alert alert-warning py-2 small mb-2">
                            <i class="bi bi-hourglass-split me-1"></i> <strong>Pending Approval:</strong> Waiting for review by authorized supervisor/manager.
                        </div>

                        @if(auth()->id() !== $discrepancy->created_by || auth()->user()->isSuperAdmin())
                            <div class="d-flex gap-2">
                                <form method="POST" action="{{ route('inventory.discrepancies.write_off.approve', $discrepancy->id) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Approve write-off? This will adjust inventory and post loss entries to the General Ledger.')">
                                        <i class="bi bi-check-circle me-1"></i> Approve Write-Off
                                    </button>
                                </form>
                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectWriteOffModal">
                                    <i class="bi bi-x-circle me-1"></i> Reject Write-Off
                                </button>
                            </div>
                        @else
                            <div class="badge bg-secondary p-2">
                                <i class="bi bi-lock me-1"></i> Separation of Duties: You submitted this request and cannot approve your own write-off.
                            </div>
                        @endif
                    @else
                        <form method="POST" action="{{ route('inventory.discrepancies.write_off.submit', $discrepancy->id) }}">
                            @csrf
                            <input type="hidden" name="reason" value="Transit loss investigation confirmed {{ $discrepancy->discrepancy_type }}: {{ $discrepancy->reason }}">
                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Submit this discrepancy for formal write-off approval?')">
                                <i class="bi bi-send me-1"></i> Submit for Write-Off Approval
                            </button>
                        </form>
                    @endif
                </div>

                <!-- Option 2: Dispatch Replacement or Separate Delivery -->
                <div class="col-md-6">
                    <h6 class="fw-bold"><i class="bi bi-box-arrow-right me-1"></i> Option A: Send Replacement Dispatch</h6>
                    <p class="small text-muted mb-2">Warehouse confirms missing quantity is available and dispatches an additional shipment.</p>
                    <button type="button" class="btn btn-sm btn-outline-primary mb-3" data-bs-toggle="modal" data-bs-target="#replacementModal">
                        <i class="bi bi-truck me-1"></i> Dispatch Replacement Stock
                    </button>

                    <h6 class="fw-bold"><i class="bi bi-box-arrow-in-down me-1"></i> Option B: Confirm Separate Delivery</h6>
                    <p class="small text-muted mb-2">Missing goods arrived separately on a subsequent vehicle.</p>
                    <button type="button" class="btn btn-sm btn-outline-success mb-3" data-bs-toggle="modal" data-bs-target="#separateDeliveryModal">
                        <i class="bi bi-check2-square me-1"></i> Confirm Delayed Delivery Received
                    </button>

                    <h6 class="fw-bold text-muted"><i class="bi bi-x-octagon me-1"></i> Option D: Cancel Outstanding Requirement</h6>
                    <p class="small text-muted mb-2">Department no longer needs the missing quantity.</p>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#cancelModal">
                        <i class="bi bi-slash-circle me-1"></i> Cancel Outstanding Requirement
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif

<!-- Replacement Dispatch Modal -->
<div class="modal fade" id="replacementModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('inventory.discrepancies.additional_dispatch', $discrepancy->id) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Dispatch Replacement Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Dispatch remaining quantity of <strong>{{ $discrepancy->item->name }}</strong> from warehouse.</p>
                    <div class="mb-3">
                        <label class="form-label">Replacement Quantity</label>
                        <input type="number" step="0.001" min="0.001" max="{{ $discrepancy->discrepancy_qty }}" name="quantity" class="form-control" value="{{ $discrepancy->discrepancy_qty }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Batch</label>
                        <select name="batch_no" class="form-select" required>
                            @foreach($sourceBatches as $sb)
                                <option value="{{ $sb->batch_no }}">{{ $sb->batch_no }} (Available: {{ $sb->quantity }})</option>
                            @endforeach
                            @if($sourceBatches->isEmpty())
                                <option value="DEFAULT">DEFAULT</option>
                            @endif
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-truck me-1"></i> Create Replacement Dispatch</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Separate Delivery Modal -->
<div class="modal fade" id="separateDeliveryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('inventory.discrepancies.separate_delivery', $discrepancy->id) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Confirm Separate Delivery Received</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Record goods that were missing in original transit but have now arrived.</p>
                    <div class="mb-3">
                        <label class="form-label">Delivered Quantity</label>
                        <input type="number" step="0.001" min="0.001" max="{{ $discrepancy->discrepancy_qty }}" name="delivered_quantity" class="form-control" value="{{ $discrepancy->discrepancy_qty }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Delivery Note / Proof</label>
                        <textarea name="notes" class="form-control" rows="2" required placeholder="e.g. Arrived via Van #2 on afternoon run"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i> Confirm Received</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Cancel Requirement Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('inventory.discrepancies.cancel', $discrepancy->id) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Cancel Outstanding Quantity</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Close this discrepancy by cancelling the outstanding requirement. No further stock movement will take place.</p>
                    <div class="mb-3">
                        <label class="form-label">Cancellation Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="2" required placeholder="e.g. Production order scale reduced"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-secondary"><i class="bi bi-slash-circle me-1"></i> Cancel Requirement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Write-Off Modal -->
<div class="modal fade" id="rejectWriteOffModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('inventory.discrepancies.write_off.reject', $discrepancy->id) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-danger">Reject Write-Off</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Rejection Reason</label>
                        <textarea name="reason" class="form-control" rows="2" required placeholder="Explain why write-off cannot be authorized..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger">Confirm Rejection</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
