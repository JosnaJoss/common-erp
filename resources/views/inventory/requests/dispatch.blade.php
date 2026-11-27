@extends('layouts.app')

@section('title', 'Prepare Stock Dispatch: ' . $stockRequest->request_no)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Stock Dispatch Preparation</h4>
        <p class="text-muted small mb-0">Select warehouse batches and allocate quantities for Request #{{ $stockRequest->request_no }}</p>
    </div>
    <a href="{{ route('inventory.requests.show', $stockRequest->id) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Request
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

<form method="POST" action="{{ route('inventory.requests.dispatch.store', $stockRequest->id) }}">
    @csrf

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 border-0">
            <h5 class="fw-bold mb-0 text-primary"><i class="bi bi-truck me-2"></i>Dispatch Details</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label text-muted small">Source Warehouse</label>
                    <div class="fw-bold">{{ $stockRequest->sourceGodown->name ?? 'N/A' }}</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted small">Destination</label>
                    <div class="fw-bold">{{ $stockRequest->fromDepartment->name ?? 'Department' }} ({{ $stockRequest->fromGodown->name ?? 'Direct' }})</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted small">Dispatch Reference / Waybill #</label>
                    <input type="text" name="dispatch_reference" class="form-control form-control-sm" placeholder="e.g. TR-DISP-001">
                </div>
                <div class="col-md-12">
                    <label class="form-label text-muted small">Dispatch Remarks</label>
                    <input type="text" name="remarks" class="form-control form-control-sm" placeholder="Driver notes, delivery vehicle, etc.">
                </div>
            </div>
        </div>
    </div>

    <!-- Allocation Matrix -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 border-0">
            <h5 class="fw-bold mb-0 text-primary"><i class="bi bi-boxes me-2"></i>Batch Selection & Allocation</h5>
            <small class="text-muted">Allocate quantities from specific batches available in the source warehouse.</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 30%;">Item / Approved</th>
                            <th style="width: 35%;">Select Batch (Available In Warehouse) <span class="text-danger">*</span></th>
                            <th style="width: 35%;">Dispatch Quantity <span class="text-danger">*</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stockRequest->items as $idx => $item)
                            @php
                                $remaining = max(0, (float)$item->approved_qty - (float)$item->dispatched_qty);
                                $batches = $itemBatches[$item->id] ?? collect();
                            @endphp
                            @if($remaining > 0)
                                <tr>
                                    <td>
                                        <input type="hidden" name="items[{{ $idx }}][stock_request_item_id]" value="{{ $item->id }}">
                                        <strong>{{ $item->item->name }}</strong>
                                        <div class="small text-muted">
                                            Approved: {{ number_format($item->approved_qty, 3) }} | Remaining: <span class="badge bg-primary">{{ number_format($remaining, 3) }} {{ $item->item->unit->symbol ?? '' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <select name="items[{{ $idx }}][batch_no]" class="form-select form-select-sm" required>
                                            @forelse($batches as $b)
                                                <option value="{{ $b->batch_no }}">
                                                    Batch: {{ $b->batch_no }} (Available: {{ number_format($b->quantity, 3) }}{{ $b->expiry_date ? ', Exp: ' . $b->expiry_date->format('d/m/Y') : '' }})
                                                </option>
                                            @empty
                                                <option value="DEFAULT">DEFAULT (No batch records)</option>
                                            @endforelse
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" step="0.001" min="0.001" max="{{ $remaining }}"
                                            name="items[{{ $idx }}][dispatch_quantity]"
                                            class="form-control form-control-sm text-end"
                                            value="{{ $remaining }}" required>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white py-3 text-end">
            <button type="submit" class="btn btn-success px-4" onclick="return confirm('Confirm dispatch? Stock will be deducted from source warehouse and placed In-Transit.')">
                <i class="bi bi-truck me-1"></i> Confirm & Dispatch (Move to In-Transit)
            </button>
        </div>
    </div>
</form>
@endsection
