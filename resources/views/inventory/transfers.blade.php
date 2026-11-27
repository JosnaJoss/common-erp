@extends('layouts.app')

@section('title', 'Inter-Godown Stock Transfers')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Inter-Godown Stock Transfers</h4>
        <p class="text-muted small mb-0">Transfer inventory seamlessly between branch godowns with real-time stock relocation</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('inventory.requests.index') }}" class="btn btn-outline-primary">
            <i class="bi bi-file-earmark-text me-1"></i> Stock Requests
        </a>
        <a href="{{ route('inventory.discrepancies.index') }}" class="btn btn-outline-danger">
            <i class="bi bi-exclamation-triangle me-1"></i> Discrepancies
        </a>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newTransferModal">
            <i class="bi bi-arrow-left-right me-1"></i> New Direct Transfer
        </button>
    </div>
</div>

<!-- Transfer List -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Transfer #</th>
                        <th>Date</th>
                        <th>Source Godown</th>
                        <th>Destination Godown</th>
                        <th>Transferred Items</th>
                        <th>Status</th>
                        <th>Remarks / Timeline</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transfers as $trf)
                        <tr>
                            <td><span class="badge bg-light text-primary border fw-bold">{{ $trf->transfer_no }}</span></td>
                            <td>{{ \Carbon\Carbon::parse($trf->transfer_date)->format('d M Y') }}</td>
                            <td>
                                <div>
                                    <i class="bi bi-box-arrow-up text-danger me-1"></i> {{ $trf->sourceGodown->name ?? 'N/A' }}
                                </div>
                                <span class="badge bg-light text-muted border" style="font-size: 0.7rem;">
                                    {{ strtoupper($trf->sourceGodown->location_type ?? 'warehouse') }}
                                </span>
                            </td>
                            <td>
                                <div>
                                    <i class="bi bi-box-arrow-down text-success me-1"></i> {{ $trf->destGodown->name ?? 'N/A' }}
                                </div>
                                <span class="badge bg-light text-muted border" style="font-size: 0.7rem;">
                                    {{ strtoupper($trf->destGodown->location_type ?? 'warehouse') }}
                                </span>
                            </td>
                            <td>
                                @foreach($trf->items as $ti)
                                    <div><strong>{{ $ti->item->name ?? 'Item' }}:</strong> {{ number_format($ti->quantity, 2) }} {{ $ti->item->unit->symbol ?? '' }}</div>
                                @endforeach
                            </td>
                            <td>
                                @switch($trf->status)
                                    @case('pending')
                                        <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Pending Dispatch</span>
                                        @break
                                    @case('in_transit')
                                        <span class="badge bg-info text-dark"><i class="bi bi-truck me-1"></i>In-Transit</span>
                                        @break
                                    @case('completed')
                                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Completed</span>
                                        @break
                                    @default
                                        <span class="badge bg-secondary">{{ ucfirst($trf->status) }}</span>
                                @endswitch
                            </td>
                            <td>
                                <small class="text-muted d-block">{{ $trf->remarks ?? '-' }}</small>
                                @if($trf->dispatched_at)
                                <small class="text-muted" style="font-size: 0.7rem;">Dispatched: {{ $trf->dispatched_at->format('d/m H:i') }}</small>
                                @endif
                                @if($trf->received_at)
                                <small class="text-success d-block" style="font-size: 0.7rem;">Received: {{ $trf->received_at->format('d/m H:i') }}</small>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($trf->status === 'pending')
                                <form method="POST" action="{{ route('inventory.transfers.dispatch', $trf->id) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-primary" onclick="return confirm('Dispatch this transfer and deduct stock from source warehouse?')">
                                        <i class="bi bi-truck me-1"></i> Dispatch
                                    </button>
                                </form>
                                @elseif(in_array($trf->status, ['in_transit', 'partially_received']))
                                <a href="{{ route('inventory.transfers.receipt.create', $trf->id) }}" class="btn btn-sm btn-success">
                                    <i class="bi bi-box-seam me-1"></i> Verify & Receive
                                </a>
                                @else
                                <span class="badge bg-light text-success border"><i class="bi bi-check-all"></i> Finalized</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-arrow-left-right fs-2 d-block mb-2"></i> No stock transfers recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($transfers->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $transfers->links() }}
        </div>
    @endif
</div>

<!-- New Transfer Modal -->
<div class="modal fade" id="newTransferModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('inventory.transfers.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-arrow-left-right me-2 text-primary"></i> Initiate Godown Transfer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Source Godown (From) <span class="text-danger">*</span></label>
                            <select name="source_godown_id" class="form-select" required>
                                <option value="">Select Origin Godown</option>
                                @foreach($godowns as $g)
                                    <option value="{{ $g->id }}">{{ $g->name }} ({{ $g->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Destination Godown (To) <span class="text-danger">*</span></label>
                            <select name="dest_godown_id" class="form-select" required>
                                <option value="">Select Destination Godown</option>
                                @foreach($godowns as $g)
                                    <option value="{{ $g->id }}">{{ $g->name }} ({{ $g->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Item to Transfer <span class="text-danger">*</span></label>
                            <select name="item_id" class="form-select" required>
                                <option value="">Select Item</option>
                                @foreach($items as $it)
                                    <option value="{{ $it->id }}">{{ $it->name }} ({{ $it->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Transfer Quantity <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="quantity" class="form-control" placeholder="e.g. 25" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Batch Number</label>
                            <input type="text" name="batch_no" class="form-control" value="DEFAULT">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Transfer Date <span class="text-danger">*</span></label>
                            <input type="date" name="transfer_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Transfer Execution Mode <span class="text-danger">*</span></label>
                            <select name="mode" class="form-select" required>
                                <option value="completed" selected>Immediate Transfer (Deduct & Receive Now)</option>
                                <option value="pending">Two-Step Workflow (Initiate Pending &rarr; Dispatch &rarr; Receive)</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Reason / Transport Remarks</label>
                            <textarea name="remarks" class="form-control" rows="2" placeholder="Vehicle, driver, or transfer notes"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i> Execute Transfer</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

