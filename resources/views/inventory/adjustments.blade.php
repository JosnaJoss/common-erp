@extends('layouts.app')

@section('title', 'Physical Stock Adjustments')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Physical Stock Adjustments</h4>
        <p class="text-muted small mb-0">Reconcile physical stock audit counts with system books and post automatic corrections</p>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newAdjustmentModal">
        <i class="bi bi-sliders me-1"></i> New Physical Adjustment
    </button>
</div>

<!-- Adjustments History -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Adjustment #</th>
                        <th>Date</th>
                        <th>Godown</th>
                        <th>Adjusted Items</th>
                        <th>Difference</th>
                        <th>Reason</th>
                        <th>Approved By</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($adjustments as $adj)
                        <tr>
                            <td><span class="badge bg-light text-dark border fw-bold">{{ $adj->adjustment_no }}</span></td>
                            <td>{{ \Carbon\Carbon::parse($adj->adjustment_date)->format('d M Y') }}</td>
                            <td><i class="bi bi-building me-1 text-muted"></i> {{ $adj->godown->name ?? 'Main Godown' }}</td>
                            <td>
                                @foreach($adj->items as $ai)
                                    <div>
                                        <strong>{{ $ai->item->name ?? 'Item' }}</strong>
                                        <small class="text-muted">(System: {{ number_format($ai->current_stock, 2) }} | Physical: {{ number_format($ai->actual_stock, 2) }})</small>
                                    </div>
                                @endforeach
                            </td>
                            <td>
                                @foreach($adj->items as $ai)
                                    <span class="badge bg-{{ $ai->type === 'increase' ? 'success' : 'danger' }}">
                                        {{ $ai->type === 'increase' ? '+' : '-' }}{{ number_format($ai->adjustment_qty, 2) }}
                                    </span>
                                @endforeach
                            </td>
                            <td><small>{{ $adj->reason }}</small></td>
                            <td><small class="text-muted">{{ $adj->approver->name ?? 'System' }}</small></td>
                            <td><span class="badge bg-success">{{ ucfirst($adj->status) }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-sliders fs-2 d-block mb-2"></i> No physical adjustments recorded.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($adjustments->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $adjustments->links() }}
        </div>
    @endif
</div>

<!-- New Adjustment Modal -->
<div class="modal fade" id="newAdjustmentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('inventory.adjustments.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-sliders me-2 text-primary"></i> Record Physical Audit Count</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Godown / Warehouse <span class="text-danger">*</span></label>
                            <select name="godown_id" class="form-select" required>
                                <option value="">Select Godown</option>
                                @foreach($godowns as $g)
                                    <option value="{{ $g->id }}">{{ $g->name }} ({{ $g->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Item <span class="text-danger">*</span></label>
                            <select name="item_id" class="form-select" required>
                                <option value="">Select Item</option>
                                @foreach($items as $it)
                                    <option value="{{ $it->id }}">{{ $it->name }} ({{ $it->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Actual Counted Physical Stock <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="actual_stock" class="form-control" placeholder="Physical quantity counted on shelf" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Audit Reason / Justification <span class="text-danger">*</span></label>
                            <input type="text" name="reason" class="form-control" placeholder="e.g. Monthly Physical Verification" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i> Apply Stock Adjustment</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

