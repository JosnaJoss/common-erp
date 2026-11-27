@extends('layouts.app')

@section('title', 'Confirm Receipt: ' . $transfer->transfer_no)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Receipt & Physical Verification: {{ $transfer->transfer_no }}</h4>
        <p class="text-muted small mb-0">Confirm accepted stock into destination location, report shortages or damaged items</p>
    </div>
    <a href="{{ route('inventory.transfers') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Transfers
    </a>
</div>

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

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <span class="text-muted small text-uppercase">Source Warehouse</span>
                <div class="fw-bold">{{ $transfer->sourceGodown->name ?? 'N/A' }}</div>
            </div>
            <div class="col-md-3">
                <span class="text-muted small text-uppercase">Destination Location</span>
                <div class="fw-bold text-success">{{ $transfer->destGodown->name ?? 'N/A' }}</div>
            </div>
            <div class="col-md-3">
                <span class="text-muted small text-uppercase">Dispatched Date</span>
                <div class="fw-bold">{{ $transfer->dispatched_at ? $transfer->dispatched_at->format('d M Y H:i') : '-' }}</div>
            </div>
            <div class="col-md-3">
                <span class="text-muted small text-uppercase">Waybill / Reference</span>
                <div class="fw-bold">{{ $transfer->dispatch_reference ?: 'None' }}</div>
            </div>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('inventory.transfers.receipt.store', $transfer->id) }}">
    @csrf

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 border-0">
            <h5 class="fw-bold mb-0 text-primary"><i class="bi bi-clipboard-check me-2"></i>Physical Count & Discrepancy Recording</h5>
            <small class="text-muted">Enter the accepted quantity to inward into the destination location. If items are damaged or short, enter the respective quantities to automatically log a discrepancy.</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 25%;">Item & Batch</th>
                            <th style="width: 12%;" class="text-end">In-Transit Qty</th>
                            <th style="width: 15%;" class="text-end">Accepted Qty <span class="text-danger">*</span></th>
                            <th style="width: 15%;" class="text-end">Damaged Qty</th>
                            <th style="width: 18%;">Discrepancy Type</th>
                            <th style="width: 15%;">Reason / Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transfer->items as $idx => $item)
                            @php
                                $inTransit = (float)$item->in_transit_qty > 0 ? (float)$item->in_transit_qty : (float)$item->dispatched_qty;
                            @endphp
                            <tr>
                                <td>
                                    <input type="hidden" name="items[{{ $idx }}][transfer_item_id]" value="{{ $item->id }}">
                                    <span class="fw-bold">{{ $item->item->name }}</span>
                                    <div class="small text-muted">
                                        Batch: <span class="badge bg-light text-dark border">{{ $item->batch->batch_no ?? 'DEFAULT' }}</span>
                                    </div>
                                </td>
                                <td class="text-end fw-bold text-primary">
                                    {{ number_format($inTransit, 3) }}
                                    <div class="small text-muted">{{ $item->item->unit->symbol ?? '' }}</div>
                                </td>
                                <td>
                                    <input type="number" step="0.001" min="0" max="{{ $inTransit }}"
                                        name="items[{{ $idx }}][received_qty]"
                                        class="form-control form-control-sm text-end fw-bold"
                                        value="{{ $inTransit }}" required>
                                </td>
                                <td>
                                    <input type="number" step="0.001" min="0" max="{{ $inTransit }}"
                                        name="items[{{ $idx }}][damaged_qty]"
                                        class="form-control form-control-sm text-end"
                                        value="0.000">
                                </td>
                                <td>
                                    <select name="items[{{ $idx }}][discrepancy_type]" class="form-select form-select-sm">
                                        <option value="">None (Normal)</option>
                                        <option value="shortage">Shortage (Missing)</option>
                                        <option value="damaged_goods">Damaged Goods</option>
                                        <option value="quality_rejection">Quality Rejection</option>
                                        <option value="wrong_item">Wrong Item</option>
                                        <option value="wrong_batch">Wrong Batch</option>
                                        <option value="other">Other</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="items[{{ $idx }}][discrepancy_reason]" class="form-control form-control-sm" placeholder="Reason for shortage/damage">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white py-3">
            <div class="mb-3">
                <label class="form-label small text-muted">Receiver General Remarks</label>
                <textarea name="receiver_remarks" class="form-control form-control-sm" rows="2" placeholder="Inspection notes, seal status, delivery condition..."></textarea>
            </div>
            <div class="text-end">
                <button type="submit" class="btn btn-success px-4" onclick="return confirm('Confirm receipt? Accepted quantities will be added to destination stock location.')">
                    <i class="bi bi-check-circle me-1"></i> Confirm Physical Receipt
                </button>
            </div>
        </div>
    </div>
</form>
@endsection
